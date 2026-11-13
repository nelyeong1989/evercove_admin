<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            /** @var string $selectedRole */
            $selectedRole = $form->get('role')->getData();

            $allowedRoles = ['ROLE_STAFF', 'ROLE_ADMIN'];
            $roleToAssign = in_array($selectedRole, $allowedRoles, true) ? $selectedRole : 'ROLE_STAFF';

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setRoles([$roleToAssign]);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Account "%s" created successfully!', $user->getUsername()));

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}