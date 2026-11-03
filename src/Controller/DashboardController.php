<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
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
        PaymentRepository $paymentRepository
    ): Response {
        // Count core records
        $totalRooms = $roomRepository->count([]);
        $totalReservations = $reservationRepository->count([]);
        $totalUsers = $userRepository->count([]);
        
        // Sum total revenue from payments
        $payments = $paymentRepository->findAll();
        $totalRevenue = 0;
        foreach ($payments as $payment) {
            $totalRevenue += (float) $payment->getAmount();
        }

        // Fetch 5 most recent reservations
        $recentReservations = $reservationRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('dashboard/index.html.twig', [
            'totalRooms' => $totalRooms,
            'totalReservations' => $totalReservations,
            'totalUsers' => $totalUsers,
            'totalRevenue' => $totalRevenue,
            'recentReservations' => $recentReservations,
        ]);
    }
}