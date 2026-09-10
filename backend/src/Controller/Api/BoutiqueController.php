<?php

namespace App\Controller\Api;

use App\Entity\Boutique;
use App\Entity\Vendeur;
use App\Enum\BoutiqueTheme;
use App\Service\EntitySerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api')]
class BoutiqueController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntitySerializer $serializer,
        private readonly SluggerInterface $slugger,
    ) {
    }

    /**
     * Liste des thèmes de boutique disponibles (choix à la création).
     */
    #[Route('/themes-boutique', name: 'api_themes_boutique', methods: ['GET'])]
    public function themes(): JsonResponse
    {
        $themes = [];
        foreach (BoutiqueTheme::cases() as $theme) {
            $themes[] = [
                'code' => $theme->value,
                'libelle' => ucfirst($theme->value),
                'rendu' => $theme->rendu(),
                'accent' => $theme->accent(),
            ];
        }
        return $this->json(['themes' => $themes]);
    }

    /**
     * Création de la boutique du vendeur connecté (une boutique par vendeur).
     */
    #[Route('/boutique', name: 'api_boutique_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Vendeur) {
            return $this->json(['error' => 'Seul un vendeur peut créer une boutique.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $nom = trim((string) ($payload['nom'] ?? ''));
        if ($nom === '') {
            return $this->json(['error' => 'Le nom de la boutique est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $slug = $this->genererSlugUnique($nom);

        $boutique = new Boutique();
        $boutique->setVendeur($user);
        $boutique->setNom($nom);
        $boutique->setSlug($slug);
        $boutique->setDescription($payload['description'] ?? null);
        $boutique->setBanniere($payload['banniere'] ?? null);
        $boutique->setLogo($payload['logo'] ?? null);
        $boutique->setTheme($payload['theme'] ?? BoutiqueTheme::CLASSIQUE->value);
        $boutique->setTiktokPseudo($payload['tiktokPseudo'] ?? null);

        // Palette personnalisée (optionnel).
        if (!empty($payload['customColors']) && is_array($payload['customColors'])) {
            $boutique->setCustomColors($payload['customColors']);
        }

        // Livraison V1 simplifiée : simple déclaration.
        $boutique->setALivreur((bool) ($payload['aLivreur'] ?? false));
        $boutique->setLivreurDetail($payload['livreurDetail'] ?? null);

        $this->entityManager->persist($boutique);
        $this->entityManager->flush();

        return $this->json(['boutique' => $this->serializer->boutique($boutique)], Response::HTTP_CREATED);
    }

    /**
     * Mise à jour de la boutique du vendeur connecté.
     */
    #[Route('/boutique/{id}', name: 'api_boutique_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $boutique = $this->entityManager->getRepository(Boutique::class)->find($id);

        if (!$boutique || !$user instanceof Vendeur || $boutique->getVendeur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Boutique introuvable ou non autorisée.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        if (isset($payload['nom']) && trim((string) $payload['nom']) !== '') {
            $boutique->setNom(trim((string) $payload['nom']));
        }
        if (array_key_exists('description', $payload)) {
            $boutique->setDescription($payload['description']);
        }
        if (array_key_exists('banniere', $payload)) {
            $boutique->setBanniere($payload['banniere'] ?: null);
        }
        if (array_key_exists('logo', $payload)) {
            $boutique->setLogo($payload['logo'] ?: null);
        }
        if (array_key_exists('theme', $payload) && $payload['theme']) {
            $boutique->setTheme($payload['theme']);
        }
        if (array_key_exists('tiktokPseudo', $payload)) {
            $boutique->setTiktokPseudo($payload['tiktokPseudo'] ?: null);
        }
        if (array_key_exists('aLivreur', $payload)) {
            $boutique->setALivreur((bool) $payload['aLivreur']);
        }
        if (array_key_exists('livreurDetail', $payload)) {
            $boutique->setLivreurDetail($payload['livreurDetail'] ?: null);
        }
        if (array_key_exists('customColors', $payload)) {
            $boutique->setCustomColors(is_array($payload['customColors']) ? $payload['customColors'] : null);
        }

        $this->entityManager->flush();

        return $this->json(['boutique' => $this->serializer->boutique($boutique)]);
    }

    /**
     * Page publique d'une boutique via son nom unique (slug) :
     * plateforme.com/boutique/{slug}.
     */
    #[Route('/boutique/{slug}', name: 'app_boutique', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $boutique = $this->entityManager->getRepository(Boutique::class)->findOneBySlug($slug);
        if (!$boutique) {
            return $this->json(['error' => 'Boutique introuvable.'], Response::HTTP_NOT_FOUND);
        }
        return $this->json(['boutique' => $this->serializer->boutique($boutique)]);
    }

    /**
     * Recherche de vendeurs par nom de boutique ou pseudo TikTok.
     */
    #[Route('/boutiques/recherche', name: 'api_boutique_search', methods: ['GET'])]
    public function recherche(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if ($q === '') {
            return $this->json(['boutiques' => []]);
        }
        $resultats = $this->entityManager->getRepository(Boutique::class)->search($q);
        return $this->json([
            'boutiques' => array_map(fn (Boutique $b) => $this->serializer->boutiqueResume($b), $resultats),
        ]);
    }

    /**
     * Boutique du vendeur connecté (dashboard).
     */
    #[Route('/ma-boutique', name: 'api_ma_boutique', methods: ['GET'])]
    public function maBoutique(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Vendeur) {
            return $this->json(['error' => 'Non autorisé.'], Response::HTTP_FORBIDDEN);
        }
        $boutique = $user->getBoutiqueActive();
        if (!$boutique) {
            return $this->json(['boutique' => null]);
        }
        return $this->json(['boutique' => $this->serializer->boutique($boutique)]);
    }

    private function genererSlugUnique(string $nom): string
    {
        $base = $this->slugger->slug($nom)->lower()->toString();
        if (empty($base)) {
            $base = 'boutique';
        }
        $slug = $base;
        $i = 1;
        while ($this->entityManager->getRepository(Boutique::class)->findOneBySlug($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}