<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
#[IsGranted('ROLE_STAFF')]
final class ReservationController extends AbstractController
{
    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository, EntityManagerInterface $entityManager): Response
    {
        $this->autoCompletePastReservations($entityManager);

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $currentUser = $this->getUser();

        if ($currentUser instanceof User) {
            $reservation->setUser($currentUser);
        }

        $isStaffOnly = !$this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(ReservationType::class, $reservation, [
            'lock_staff_account' => $isStaffOnly,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($currentUser instanceof User) {
                $reservation->setCreatedBy($currentUser);
                if ($isStaffOnly) {
                    $reservation->setUser($currentUser);
                }
            }

            $checkIn = $reservation->getCheckIn();
            $checkOut = $reservation->getCheckOut();

            if ($checkIn && $checkOut && $checkOut <= $checkIn) {
                $form->get('checkOut')->addError(new FormError('Check-out date must be at least 1 day after the check-in date.'));
            } elseif ($conflict = $this->findConflictingReservation($entityManager, $reservation)) {
                $form->get('room')->addError(new FormError(sprintf(
                    '%s is already in use from %s to %s (Booking #%d — %s). Please select another room or different dates.',
                    $reservation->getRoom()->getName(),
                    $conflict->getCheckIn()->format('M d, Y'),
                    $conflict->getCheckOut()->format('M d, Y'),
                    $conflict->getId(),
                    $conflict->getStatus()
                )));
            } else {
                $this->calculateReservationTotal($reservation);

                $entityManager->persist($reservation);
                $entityManager->flush();

                $this->addFlash('success', 'Reservation created and total calculated automatically!');

                return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
            'bookedRanges' => $this->getActiveBookingRanges($entityManager),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('RECORD_EDIT', subject: 'reservation')]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $isStaffOnly = !$this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(ReservationType::class, $reservation, [
            'lock_staff_account' => $isStaffOnly,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $checkIn = $reservation->getCheckIn();
            $checkOut = $reservation->getCheckOut();

            if ($checkIn && $checkOut && $checkOut <= $checkIn) {
                $form->get('checkOut')->addError(new FormError('Check-out date must be at least 1 day after the check-in date.'));
            } elseif ($conflict = $this->findConflictingReservation($entityManager, $reservation)) {
                $form->get('room')->addError(new FormError(sprintf(
                    '%s is already in use from %s to %s (Booking #%d — %s). Please select another room or different dates.',
                    $reservation->getRoom()->getName(),
                    $conflict->getCheckIn()->format('M d, Y'),
                    $conflict->getCheckOut()->format('M d, Y'),
                    $conflict->getId(),
                    $conflict->getStatus()
                )));
            } else {
                $this->calculateReservationTotal($reservation);
                $refundedCount = $this->syncCancellationWithPayments($entityManager, $reservation);

                $entityManager->flush();

                if ($reservation->getStatus() === 'Cancelled' && $refundedCount > 0) {
                    $this->addFlash('success', 'Reservation cancelled, room dates released, and linked payment marked as Refunded!');
                } elseif ($reservation->getStatus() === 'Cancelled') {
                    $this->addFlash('success', 'Reservation cancelled and room dates released!');
                } else {
                    $this->addFlash('success', 'Reservation updated and total recalculated!');
                }

                return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
            'bookedRanges' => $this->getActiveBookingRanges($entityManager, $reservation->getId()),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('RECORD_DELETE', subject: 'reservation')]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Reservation deleted.');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    private function autoCompletePastReservations(EntityManagerInterface $entityManager): void
    {
        $today = new \DateTimeImmutable('today');

        $pastReservations = $entityManager->getRepository(Reservation::class)->createQueryBuilder('r')
            ->where('r.status = :confirmed')
            ->andWhere('r.checkOut < :today')
            ->setParameter('confirmed', 'Confirmed')
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        if (count($pastReservations) > 0) {
            foreach ($pastReservations as $reservation) {
                $reservation->setStatus('Completed');
            }
            $entityManager->flush();
        }
    }

    private function syncCancellationWithPayments(EntityManagerInterface $entityManager, Reservation $reservation): int
    {
        if ($reservation->getStatus() !== 'Cancelled') {
            return 0;
        }

        $payments = $entityManager->getRepository(Payment::class)->findBy([
            'reservation' => $reservation,
        ]);

        $refundedCount = 0;
        foreach ($payments as $payment) {
            if ($payment->getPaymentStatus() === 'Paid') {
                $payment->setPaymentStatus('Refunded');
                $refundedCount++;
            } elseif ($payment->getPaymentStatus() === 'Pending') {
                $payment->setPaymentStatus('Failed');
            }
        }

        return $refundedCount;
    }

    private function findConflictingReservation(EntityManagerInterface $entityManager, Reservation $reservation): ?Reservation
    {
        $room = $reservation->getRoom();
        $checkIn = $reservation->getCheckIn();
        $checkOut = $reservation->getCheckOut();

        if (!$room || !$checkIn || !$checkOut) {
            return null;
        }

        if (!in_array($reservation->getStatus(), ['Pending', 'Confirmed'], true)) {
            return null;
        }

        $qb = $entityManager->getRepository(Reservation::class)->createQueryBuilder('r')
            ->where('r.room = :room')
            ->andWhere('r.status IN (:activeStatuses)')
            ->andWhere('r.checkIn < :checkOut')
            ->andWhere('r.checkOut > :checkIn')
            ->setParameter('room', $room)
            ->setParameter('activeStatuses', ['Pending', 'Confirmed'])
            ->setParameter('checkIn', $checkIn)
            ->setParameter('checkOut', $checkOut)
            ->setMaxResults(1);

        if ($reservation->getId() !== null) {
            $qb->andWhere('r.id != :currentId')
               ->setParameter('currentId', $reservation->getId());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function getActiveBookingRanges(EntityManagerInterface $entityManager, ?int $excludeId = null): array
    {
        $qb = $entityManager->getRepository(Reservation::class)->createQueryBuilder('r')
            ->where('r.status IN (:activeStatuses)')
            ->setParameter('activeStatuses', ['Pending', 'Confirmed'])
            ->orderBy('r.checkIn', 'ASC');

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        $ranges = [];
        foreach ($qb->getQuery()->getResult() as $existing) {
            if ($existing->getRoom() && $existing->getCheckIn() && $existing->getCheckOut()) {
                $ranges[] = [
                    'id' => $existing->getId(),
                    'roomId' => $existing->getRoom()->getId(),
                    'checkIn' => $existing->getCheckIn()->format('Y-m-d'),
                    'checkOut' => $existing->getCheckOut()->format('Y-m-d'),
                    'checkInLabel' => $existing->getCheckIn()->format('M d, Y'),
                    'checkOutLabel' => $existing->getCheckOut()->format('M d, Y'),
                    'status' => $existing->getStatus(),
                ];
            }
        }

        return $ranges;
    }

    private function calculateReservationTotal(Reservation $reservation): void
    {
        $room = $reservation->getRoom();
        $checkIn = $reservation->getCheckIn();
        $checkOut = $reservation->getCheckOut();

        if ($room && $checkIn && $checkOut) {
            $nights = (int) $checkIn->diff($checkOut)->format('%r%a');
            if ($nights < 1) {
                $nights = 1;
            }

            $total = (float) $room->getPricePerNight() * $nights;
            $reservation->setTotalPrice(number_format($total, 2, '.', ''));
        }
    }
}