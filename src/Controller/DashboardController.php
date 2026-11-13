<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\PaymentRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'app_dashboard')]
    #[Route('/dashboard', name: 'app_dashboard_alt')]
    public function index(
        RoomRepository $roomRepository,
        ReservationRepository $reservationRepository,
        UserRepository $userRepository,
        PaymentRepository $paymentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Automatically mark past Confirmed bookings as Completed
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

        // Count core records
        $totalRooms = $roomRepository->count([]);
        $totalReservations = $reservationRepository->count([]);
        $totalUsers = $userRepository->count([]);

        // Sum total collected revenue from Paid payments
        $payments = $paymentRepository->findBy(['paymentStatus' => 'Paid']);
        $totalRevenue = 0.0;
        foreach ($payments as $payment) {
            $totalRevenue += (float) $payment->getAmount();
        }

        // Fetch recent reservations across all statuses for instant tab filtering
        $recentReservations = $reservationRepository->findBy([], ['id' => 'DESC'], 25);

        $statusCounts = [
            'All' => count($recentReservations),
            'Confirmed' => 0,
            'Pending' => 0,
            'Cancelled' => 0,
            'Completed' => 0,
        ];

        foreach ($recentReservations as $reservation) {
            $status = $reservation->getStatus();
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'totalRooms' => $totalRooms,
            'totalReservations' => $totalReservations,
            'totalUsers' => $totalUsers,
            'totalRevenue' => $totalRevenue,
            'recentReservations' => $recentReservations,
            'statusCounts' => $statusCounts,
        ]);
    }
}