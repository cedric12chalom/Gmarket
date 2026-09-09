<?php

namespace App\Controller\Api;

use App\Entity\Acheteur;
use App\Entity\User;
use App\Enum\StatutUser;
use App\Service\EntitySerializer;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractController
{
    use TokenAwareControllerTrait;

    private TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[Route('', name: 'api_utilisateurs_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $this->requireRole($user, 'admin');
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per_page', 20);
        $all = $em->getRepository(User::class)->findAll();
        return new JsonResponse(array_slice(array_map(fn(User $u) => EntitySerializer::user($u), $all), ($page - 1) * $perPage, $perPage));
    }

    #[Route('/{id}', name: 'api_utilisateurs_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
        $this->requireRole($user, 'admin');
        $target = $em->getRepository(User::class)->find($id);
        if (!$target instanceof User) {
            return $this->error('Utilisateur non trouvé.', 404);
        }
        return new JsonResponse(EntitySerializer::user($target));
    }

    #[Route('/{id}', name: 'api_utilisateurs_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $target = $em->getRepository(User::class)->find($id);
            if (!$target instanceof User) {
                return $this->error('Utilisateur non trouvé.', 404);
            }
            $data = json_decode($request->getContent(), true) ?? [];
            if (isset($data['statut'])) {
                $target->setStatut($data['statut']);
            }
            if (isset($data['nom'])) {
                $target->setNom($data['nom']);
            }
            if (isset($data['prenom'])) {
                $target->setPrenom($data['prenom']);
            }
            if (isset($data['telephone'])) {
                $target->setTelephone($data['telephone']);
            }
            if (isset($data['ville'])) {
                $target->setVille($data['ville']);
            }
            if (isset($data['quartier'])) {
                $target->setQuartier($data['quartier']);
            }
            $em->flush();
            return new JsonResponse(EntitySerializer::user($target));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/profile', name: 'api_utilisateurs_profile', methods: ['PUT'])]
    public function profile(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $data = json_decode($request->getContent(), true) ?? [];
            if ($user instanceof Acheteur && (isset($data['latitude']) || isset($data['longitude']) || isset($data['localisation']))) {
                return $this->error('Votre position de livraison est figée : elle a été enregistrée de façon définitive à l\'inscription et ne peut plus être modifiée.', 400);
            }
            if (isset($data['nom'])) {
                $user->setNom($data['nom']);
            }
            if (isset($data['prenom'])) {
                $user->setPrenom($data['prenom']);
            }
            if (isset($data['telephone'])) {
                $user->setTelephone($data['telephone']);
            }
            if (isset($data['localisation'])) {
                $user->setLocalisation($data['localisation']);
            }
            if (isset($data['latitude']) || isset($data['longitude'])) {
                $lat = $data['latitude'] ?? '';
                $lng = $data['longitude'] ?? '';
                if ($lat === '' || $lng === '' || $lat === null || $lng === null) {
                    return $this->error('Latitude et longitude doivent être fournies ensemble.', 400);
                }
                $user->setLatitude((string) $lat);
                $user->setLongitude((string) $lng);
            }
            if (isset($data['ville'])) {
                $user->setVille($data['ville']);
            }
            if (isset($data['quartier'])) {
                $user->setQuartier($data['quartier']);
            }
            if (isset($data['adresse_livraison'])) {
                $user->setAdresseLivraison($data['adresse_livraison']);
            }
            if (isset($data['type_transport'])) {
                $user->setTypeTransport($data['type_transport']);
            }
            if ($user instanceof \App\Entity\Livreur) {
                foreach ([
                    'cni_numero' => 'setCniNumero',
                    'cni_photo' => 'setCniPhoto',
                    'photo_profil' => 'setPhotoProfil',
                    'moyen_deplacement' => 'setMoyenDeplacement',
                    'vehicule_plaque' => 'setVehiculePlaque',
                    'vehicule_marque_modele' => 'setVehiculeMarqueModele',
                    'mobile_money_numero' => 'setMobileMoneyNumero',
                    'contact_urgence_nom' => 'setContactUrgenceNom',
                    'contact_urgence_telephone' => 'setContactUrgenceTelephone',
                ] as $field => $setter) {
                    if (isset($data[$field])) {
                        $user->{$setter}($data[$field] !== '' ? $data[$field] : null);
                    }
                }
                if (!empty($data['conditions_livraison_accepte']) && !$user->getConditionsLivraisonAccepteLe()) {
                    $user->setConditionsLivraisonAccepteLe(new \DateTime());
                }
            }
            $em->flush();
            return new JsonResponse(EntitySerializer::user($user));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    #[Route('/{id}/suspendre', name: 'api_utilisateurs_suspendre', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function suspendre(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $user = $this->requireUser($this->getCurrentUser($request, $em, $this->tokenService));
            $this->requireRole($user, 'admin');
            $target = $em->getRepository(User::class)->find($id);
            if (!$target instanceof User) {
                return $this->error('Utilisateur non trouvé.', 404);
            }
            $target->setStatut(StatutUser::SUSPENDU->value);
            $em->flush();
            return new JsonResponse(EntitySerializer::user($target));
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}
