<?php

namespace App\Controller\Api;

use App\Entity\Acheteur;
use App\Entity\Administrateur;
use App\Entity\Livreur;
use App\Entity\Producteur;
use App\Entity\User;
use App\Enum\Role;
use App\Service\EntitySerializer;
use App\Service\NotificationService;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;
    private UserPasswordHasherInterface $passwordHasher;
    private NotificationService $notificationService;

    public function __construct(TokenService $tokenService, UserPasswordHasherInterface $passwordHasher, NotificationService $notificationService)
    {
        $this->tokenService = $tokenService;
        $this->passwordHasher = $passwordHasher;
        $this->notificationService = $notificationService;
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $email = $data['email'] ?? '';
            $password = $data['mot_de_passe'] ?? '';
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user instanceof User || !$this->passwordHasher->isPasswordValid($user, $password)) {
                return $this->error('Email ou mot de passe incorrect.', 401);
            }
            if (!$user->isActif()) {
                return $this->error('Compte suspendu.', 403);
            }
            if (!$user->isEmailVerifie()) {
                return new JsonResponse([
                    'message' => 'Email non vérifié. Veuillez vérifier votre boîte de réception.',
                    'code' => 403,
                    'email_verifie' => false,
                ], 403);
            }
            $token = $this->tokenService->generate([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
            ]);
            return new JsonResponse(['token' => $token, 'user' => EntitySerializer::user($user)]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $required = ['nom', 'prenom', 'email', 'telephone', 'mot_de_passe', 'role'];
            if (empty($data['cgu_accepte'])) {
                return $this->error('Vous devez accepter les conditions d\'utilisation.', 400);
            }
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->error("Champ requis manquant : {$field}.", 400);
                }
            }
            $password = (string) $data['mot_de_passe'];
            if (
                strlen($password) < 8
                || !preg_match('/[A-Za-z]/', $password)
                || !preg_match('/[0-9]/', $password)
                || !preg_match('/[^A-Za-z0-9]/', $password)
            ) {
                return $this->error('Le mot de passe doit contenir au moins 8 caractères, une lettre, un chiffre et un caractère spécial.', 400);
            }
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existing instanceof User) {
                return $this->error('Un utilisateur avec cet email existe déjà.', 409);
            }
            $user = match ($data['role']) {
                Role::PRODUCTEUR->value => new Producteur(),
                Role::ACHETEUR->value => new Acheteur(),
                Role::LIVREUR->value => new Livreur(),
                Role::ADMIN->value => new Administrateur(),
                default => new User(),
            };
            $user->setEmail($data['email']);
            $user->setNom($data['nom']);
            $user->setPrenom($data['prenom']);
            $user->setTelephone($data['telephone']);
            $user->setRoles([
    'ROLE_USER'
]);
            if (isset($data['localisation'])) {
                $user->setLocalisation($data['localisation']);
            }
            if (isset($data['ville'])) {
                $user->setVille($data['ville']);
            }
            if (isset($data['quartier'])) {
                $user->setQuartier($data['quartier']);
            }
            if ($user instanceof Acheteur) {
                $lat = $data['latitude'] ?? '';
                $lng = $data['longitude'] ?? '';
                if ($lat === '' || $lng === '' || $lat === null || $lng === null) {
                    return $this->error('Votre position de livraison est obligatoire : sans position, seul le mode retrait sera disponible, pas la livraison. Cette position est enregistrée de façon définitive à l\'inscription.', 400);
                }
                $user->setLatitude((string) $lat);
                $user->setLongitude((string) $lng);
            } elseif (isset($data['latitude']) || isset($data['longitude'])) {
                $lat = $data['latitude'] ?? '';
                $lng = $data['longitude'] ?? '';
                if ($lat === '' || $lng === '' || $lat === null || $lng === null) {
                    return $this->error('Latitude et longitude doivent être fournies ensemble.', 400);
                }
                $user->setLatitude((string) $lat);
                $user->setLongitude((string) $lng);
            }
            if (isset($data['adresse_livraison'])) {
                $user->setAdresseLivraison($data['adresse_livraison']);
            }
            if (isset($data['type_transport'])) {
                $user->setTypeTransport($data['type_transport']);
            }
            if ($user instanceof Livreur) {
                if (isset($data['cni_numero'])) {
                    $user->setCniNumero($data['cni_numero']);
                }
                if (isset($data['cni_photo'])) {
                    $user->setCniPhoto($data['cni_photo']);
                }
                if (isset($data['photo_profil'])) {
                    $user->setPhotoProfil($data['photo_profil']);
                }
                if (isset($data['moyen_deplacement'])) {
                    $user->setMoyenDeplacement($data['moyen_deplacement']);
                }
                if (isset($data['vehicule_plaque'])) {
                    $user->setVehiculePlaque(in_array($data['moyen_deplacement'] ?? '', ['moto', 'voiture', 'camionnette'], true) ? $data['vehicule_plaque'] : null);
                }
                if (isset($data['vehicule_marque_modele'])) {
                    $user->setVehiculeMarqueModele(($data['moyen_deplacement'] ?? '') === 'pied' ? null : $data['vehicule_marque_modele']);
                }
                if (isset($data['mobile_money_numero'])) {
                    $user->setMobileMoneyNumero($data['mobile_money_numero']);
                }
                if (isset($data['contact_urgence_nom'])) {
                    $user->setContactUrgenceNom($data['contact_urgence_nom']);
                }
                if (isset($data['contact_urgence_telephone'])) {
                    $user->setContactUrgenceTelephone($data['contact_urgence_telephone']);
                }
                if (!empty($data['conditions_livraison_accepte'])) {
                    $user->setConditionsLivraisonAccepteLe(new \DateTime());
                }
            }
            $hashed = $this->passwordHasher->hashPassword($user, $data['mot_de_passe']);
            $user->setPassword($hashed);
            $user->setCguAccepteLe(new \DateTime());
            $user->setEmailVerifie(false);
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setCodeVerification($code);
            $user->setCodeVerificationExpireLe(new \DateTime('+15 minutes'));
            $em->persist($user);
            $em->flush();
            $this->notificationService->envoyerEmail(
                $user->getEmail(),
                'TerraLink — Vérification de votre adresse email',
                "Bonjour {$user->getPrenom()},\n\nVoici votre code de vérification TerraLink :\n\n{$code}\n\nCe code expire dans 15 minutes.\n\nL'équipe TerraLink"
            );
            return new JsonResponse([
                'message' => 'Compte créé. Veuillez vérifier votre boîte email pour recevoir le code de vérification.',
                'user' => EntitySerializer::user($user),
                'email_verifie' => false,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/verify-email', name: 'api_auth_verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $code = $data['code'] ?? '';
            $email = $data['email'] ?? '';
            if ($code === '' || $email === '') {
                return $this->error('Email et code de vérification requis.', 400);
            }
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user instanceof User) {
                return $this->error('Utilisateur introuvable.', 404);
            }
            if ($user->isEmailVerifie()) {
                return new JsonResponse(['message' => 'Email déjà vérifié.', 'email_verifie' => true]);
            }
            if ($user->getCodeVerification() !== $code) {
                return $this->error('Code de vérification incorrect.', 400);
            }
            if ($user->getCodeVerificationExpireLe() instanceof \DateTime && $user->getCodeVerificationExpireLe() < new \DateTime()) {
                return $this->error('Le code a expiré. Veuillez vous reconnecter pour recevoir un nouveau code.', 400);
            }
            $user->setEmailVerifie(true);
            $user->setCodeVerification(null);
            $user->setCodeVerificationExpireLe(null);
            $em->flush();
            $this->notificationService->envoyerEmail(
                $user->getEmail(),
                'TerraLink — Bienvenue !',
                "Bonjour {$user->getPrenom()},\n\nVotre adresse email a été vérifiée avec succès. Bienvenue sur TerraLink !\n\nL'équipe TerraLink"
            );
            return new JsonResponse([
                'token' => $this->tokenService->generate([
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                ]),
                'user' => EntitySerializer::user($user),
                'email_verifie' => true,
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/resend-verification', name: 'api_auth_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $email = $data['email'] ?? '';
            if ($email === '') {
                return $this->error('Email requis.', 400);
            }
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user instanceof User) {
                return new JsonResponse(['message' => 'Si cet email existe, un code a été envoyé.']);
            }
            if ($user->isEmailVerifie()) {
                return new JsonResponse(['message' => 'Email déjà vérifié.']);
            }
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->setCodeVerification($code);
            $user->setCodeVerificationExpireLe(new \DateTime('+15 minutes'));
            $em->flush();
            $this->notificationService->envoyerEmail(
                $user->getEmail(),
                'TerraLink — Nouveau code de vérification',
                "Bonjour {$user->getPrenom()},\n\nVoici votre nouveau code de vérification :\n\n{$code}\n\nCe code expire dans 15 minutes.\n\nL'équipe TerraLink"
            );
            return new JsonResponse(['message' => 'Un nouveau code a été envoyé à votre adresse email.']);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/google', name: 'api_auth_google', methods: ['POST'])]
    public function google(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $googleId = $data['google_id'] ?? '';
            $email = $data['email'] ?? '';
            $nom = $data['nom'] ?? '';
            $prenom = $data['prenom'] ?? '';
            if ($googleId === '' || $email === '') {
                return $this->error('google_id et email requis.', 400);
            }
            $user = $em->getRepository(User::class)->findOneBy(['googleId' => $googleId]);
            if (!$user instanceof User) {
                $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($user instanceof User) {
                    $user->setGoogleId($googleId);
                    $user->setEmailVerifie(true);
                }
            }
            if (!$user instanceof User) {
                $user = new Acheteur();
                $user->setEmail($email);
                $user->setNom($nom ?: 'Utilisateur');
                $user->setPrenom($prenom ?: 'Google');
                $user->setTelephone('');
                $user->setRoles(['ROLE_USER']);
                $user->setGoogleId($googleId);
                $user->setEmailVerifie(true);
                $user->setCguAccepteLe(new \DateTime());
                $em->persist($user);
            }
            $em->flush();
            $token = $this->tokenService->generate([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
            ]);
            return new JsonResponse([
                'token' => $token,
                'user' => EntitySerializer::user($user),
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return new JsonResponse(['message' => 'Déconnexion réussie.']);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            return new JsonResponse(EntitySerializer::user($user));
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), $e->getCode() >= 400 ? $e->getCode() : 401);
        }
    }
}
