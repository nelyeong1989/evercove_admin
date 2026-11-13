<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findBy([], ['id' => 'ASC']),
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleStatus(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('toggle_status' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token.');

            return $this->redirectToRoute('app_user_index');
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot deactivate your own active Admin account.');

            return $this->redirectToRoute('app_user_index');
        }

        $newStatus = !(bool) $user->isActive();
        $user->setIsActive($newStatus);
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf('Account "%s" has been %s.', $user->getUsername(), $newStatus ? 'activated' : 'disabled')
        );

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/toggle-role', name: 'app_user_toggle_role', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleRole(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('toggle_role' . $user->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Invalid security token.');

            return $this->redirectToRoute('app_user_index');
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot change your own role while signed in.');

            return $this->redirectToRoute('app_user_index');
        }

        $isCurrentlyAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $newRole = $isCurrentlyAdmin ? 'ROLE_STAFF' : 'ROLE_ADMIN';

        $user->setRoles([$newRole]);
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf('Updated "%s" role to %s.', $user->getUsername(), $newRole === 'ROLE_ADMIN' ? 'Admin' : 'Staff')
        );

        return $this->redirectToRoute('app_user_index');
    }
}