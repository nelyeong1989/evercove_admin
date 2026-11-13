<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\User;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
#[IsGranted('ROLE_STAFF')]
final class PaymentController extends AbstractController
{
    #[Route(name: 'app_payment_index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository): Response
    {
        return $this->render('payment/index.html.twig', [
            'payments' => $paymentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payment = new Payment();
        $payment->setPaidAt(new \DateTime());

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $payment->setCreatedBy($user);
            }

            $this->syncPaymentWithReservation($payment);

            $entityManager->persist($payment);
            $entityManager->flush();

            $this->addFlash(
                'success',
                $payment->getPaymentStatus() === 'Paid'
                    ? 'Payment recorded and reservation automatically confirmed!'
                    : 'Payment recorded successfully!'
            );

            return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('payment/new.html.twig', [
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_payment_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('RECORD_EDIT', $payment);

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncPaymentWithReservation($payment);
            $entityManager->flush();

            $this->addFlash('success', 'Payment updated and reservation status synchronized!');

            return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('RECORD_DELETE', $payment);

        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();
            $this->addFlash('success', 'Payment record deleted.');
        }

        return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
    }

    private function syncPaymentWithReservation(Payment $payment): void
    {
        $reservation = $payment->getReservation();
        if ($reservation === null) {
            return;
        }

        if ($payment->getAmount() === null || (float) $payment->getAmount() <= 0) {
            $payment->setAmount($reservation->getTotalPrice());
        }

        if ($payment->getPaymentStatus() === 'Paid' && $reservation->getStatus() !== 'Completed') {
            $reservation->setStatus('Confirmed');
        }
    }
}