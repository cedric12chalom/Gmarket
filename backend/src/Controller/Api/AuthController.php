<?php

namespace App\Controller\Api;

use App\Entity\Abonnement;
use App\Entity\Acheteur;
use App\Entity\Administrateur;
use App\Entity\Vendeur;
use App\Enum\Role;
use App\Enum\StatutAbonnement;
use App\Service\AbonnementService;
use App\Service\EntitySerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntitySerializer $serializer,
        private readonly ValidatorInterface $validator,
        private readonly AbonnementService $abonnementService,
    ) {
    }

    /**
     * Inscription d'un compte. Rôle attendu dans la charge utile :
     * 'acheteur' (défaut) ou 'vendeur'.
     */
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $role = (string) ($payload['role'] ?? 'acheteur');

        if ($email === '' || $password === '') {
            return $this->json(['error' => 'email et password sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'Un compte existe déjà avec cet email.'], Response::HTTP_CONFLICT);
        }

        $user = match ($role) {
            'vendeur' => new Vendeur(),
            'administrateur' => new Administrateur(),
            default => new Acheteur(),
        };

        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles([$role === 'vendeur' ? Role::VENDEUR->value : ($role === 'administrateur' ? Role::ADMIN->value : Role::ACHETEUR->value)]);

        // Profil : photo et réseaux sociaux optionnels à l'inscription.
        $user->setPhoto($payload['photo'] ?? null);
        $user->setTelephone($payload['telephone'] ?? null);
        $user->setTiktok($payload['tiktok'] ?? null);
        $user->setInstagram($payload['instagram'] ?? null);
        $user->setSnapchat($payload['snapchat'] ?? null);
        $user->setFacebook($payload['facebook'] ?? null);

        if ($user instanceof Acheteur) {
            $user->setPrenom($payload['prenom'] ?? null);
            $user->setNom($payload['nom'] ?? null);
        }
        if ($user instanceof Vendeur) {
            $user->setPrenom($payload['prenom'] ?? null);
            $user->setNom($payload['nom'] ?? null);
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['error' => implode(' ', $messages)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Un vendeur démarre systématiquement par une période d'essai gratuite.
        if ($user instanceof Vendeur) {
            $essai = $this->entityManager->getRepository(Abonnement::class)->findOneBy(['code' => 'essai']);
            if ($essai) {
                $this->abonnementService->souscrire($user, $essai, Abonnement::ESSAI_JOURS, StatutAbonnement::ESSAI);
            }
        }

        $data = $this->serializer->user($user);
        return $this->json(['user' => $data], Response::HTTP_CREATED);
    }

    /**
     * Connexion : gérée par le firewall lexik_jwt (json_login). Cette route
     * n'est jamais réellement exécutée — elle existe pour que Symfony expose
     * l'URL /api/auth/login.
     */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Renvoie le profil de l'utilisateur connecté (JWT).
     */
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->json(['user' => $this->serializer->user($user)]);
    }
}