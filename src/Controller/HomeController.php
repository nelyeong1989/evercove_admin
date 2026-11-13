<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(RoomRepository $roomRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'rooms' => $roomRepository->findBy([], ['id' => 'ASC']),
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
        ]);
    }
}