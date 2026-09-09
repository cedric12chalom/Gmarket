<?php

namespace App\Controller\Api;

use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Offre;
use App\Entity\Produit;
use App\Entity\Vendeur;
use App\Entity\Variante;
use App\Repository\ProduitRepository;
use App\Service\EntitySerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/produits')]
class ProduitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntitySerializer $serializer,
    ) {
    }

    /**
     * Catalogue public des produits actifs (filtres : categorie, boutique).
     */
    #[Route('', name: 'api_produits_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $categorieId = $request->query->getInt('categorie');
        $boutiqueId = $request->query->getInt('boutique');

        /** @var ProduitRepository $repository */
        $repository = $this->entityManager->getRepository(Produit::class);
        $produits = $repository->findActifs(
            $categorieId > 0 ? $categorieId : null,
            $boutiqueId > 0 ? $boutiqueId : null,
        );

        return $this->json([
            'produits' => array_map(fn (Produit $p) => $this->serializer->produit($p), $produits),
        ]);
    }

    /**
     * Fiche produit publique.
     */
    #[Route('/{id}', name: 'api_produits_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $produit = $this->entityManager->getRepository(Produit::class)->find($id);
        if (!$produit || !$produit->isActif()) {
            return $this->json(['error' => 'Produit introuvable.'], Response::HTTP_NOT_FOUND);
        }
        return $this->json(['produit' => $this->serializer->produit($produit)]);
    }

    /**
     * Ajout d'un produit. Les photos sont obligatoires : tant qu'aucune
     * image n'est fournie, la publication est bloquée.
     */
    #[Route('', name: 'api_produits_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $boutique = $this->boutiqueAutorisee();
        if (!$boutique) {
            return $this->json(['error' => 'Seul un vendeur avec boutique peut ajouter un produit.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $nom = trim((string) ($payload['nom'] ?? ''));
        if ($nom === '') {
            return $this->json(['error' => 'Le nom du produit est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $images = array_values(array_filter($payload['images'] ?? [], 'is_string'));
        if (count($images) === 0) {
            return $this->json(['error' => 'Au moins une photo est obligatoire pour publier un produit.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $categorie = $this->entityManager->getRepository(Categorie::class)->find($payload['categorieId'] ?? 0);
        if (!$categorie) {
            return $this->json(['error' => 'Catégorie invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $produit = new Produit();
        $produit->setBoutique($boutique);
        $produit->setCategorie($categorie);
        $produit->setNom($nom);
        $produit->setDescription($payload['description'] ?? null);
        $produit->setImages($images);
        $produit->setPrix(max(0.0, (float) ($payload['prix'] ?? 0)));
        $produit->setActif((bool) ($payload['actif'] ?? true));

        $this->appliquerVariantes($produit, $payload['variantes'] ?? []);

        $this->entityManager->persist($produit);
        $this->entityManager->flush();

        return $this->json(['produit' => $this->serializer->produit($produit)], Response::HTTP_CREATED);
    }

    /**
     * Mise à jour d'un produit (photos, prix, variantes, actif...).
     */
    #[Route('/{id}', name: 'api_produits_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $boutique = $this->boutiqueAutorisee();
        $produit = $this->entityManager->getRepository(Produit::class)->find($id);
        if (!$boutique || !$produit || $produit->getBoutique()->getId() !== $boutique->getId()) {
            return $this->json(['error' => 'Produit introuvable ou non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        if (isset($payload['nom']) && trim((string) $payload['nom']) !== '') {
            $produit->setNom(trim((string) $payload['nom']));
        }
        if (array_key_exists('description', $payload)) {
            $produit->setDescription($payload['description']);
        }
        if (array_key_exists('images', $payload)) {
            $images = array_values(array_filter($payload['images'], 'is_string'));
            if (count($images) === 0) {
                return $this->json(['error' => 'Au moins une photo est obligatoire pour publier un produit.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $produit->setImages($images);
        }
        if (array_key_exists('prix', $payload)) {
            $produit->setPrix(max(0.0, (float) $payload['prix']));
        }
        if (array_key_exists('actif', $payload)) {
            $produit->setActif((bool) $payload['actif']);
        }
        if (array_key_exists('categorieId', $payload)) {
            $categorie = $this->entityManager->getRepository(Categorie::class)->find($payload['categorieId']);
            if ($categorie) {
                $produit->setCategorie($categorie);
            }
        }

        // Remplacement des variantes (couleur + taille + stock par combinaison).
        if (array_key_exists('variantes', $payload)) {
            foreach ($produit->getVariantes() as $variante) {
                $produit->removeVariante($variante);
                $this->entityManager->remove($variante);
            }
            $this->appliquerVariantes($produit, $payload['variantes']);
        }

        $this->entityManager->flush();

        return $this->json(['produit' => $this->serializer->produit($produit)]);
    }

    /**
     * Suppression d'un produit.
     */
    #[Route('/{id}', name: 'api_produits_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $boutique = $this->boutiqueAutorisee();
        $produit = $this->entityManager->getRepository(Produit::class)->find($id);
        if (!$boutique || !$produit || $produit->getBoutique()->getId() !== $boutique->getId()) {
            return $this->json(['error' => 'Produit introuvable ou non autorisé.'], Response::HTTP_FORBIDDEN);
        }
        $this->entityManager->remove($produit);
        $this->entityManager->flush();
        return $this->json(['supprime' => true]);
    }

    /**
     * Ajoute/modifie une offre à durée limitée sur un produit.
     */
    #[Route('/{id}/offre', name: 'api_produits_offre', methods: ['PUT'])]
    public function setOffre(int $id, Request $request): JsonResponse
    {
        $boutique = $this->boutiqueAutorisee();
        $produit = $this->entityManager->getRepository(Produit::class)->find($id);
        if (!$boutique || !$produit || $produit->getBoutique()->getId() !== $boutique->getId()) {
            return $this->json(['error' => 'Produit introuvable ou non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $prixPromo = max(0.0, (float) ($payload['prixPromo'] ?? 0));
        if ($prixPromo <= 0) {
            return $this->json(['error' => 'Le prix promo doit être supérieur à 0.'], Response::HTTP_BAD_REQUEST);
        }

        $offre = $produit->getOffre();
        if (!$offre) {
            $offre = new Offre();
            $offre->setProduit($produit);
            $this->entityManager->persist($offre);
            $produit->setOffre($offre);
        }
        $offre->setPrixPromo($prixPromo);
        $offre->setDateDebut($payload['dateDebut'] ?? null ? new \DateTimeImmutable($payload['dateDebut']) : new \DateTimeImmutable());
        $offre->setDateFin($payload['dateFin'] ?? null ? new \DateTimeImmutable($payload['dateFin']) : (new \DateTimeImmutable())->modify('+7 days'));

        $this->entityManager->flush();

        return $this->json(['produit' => $this->serializer->produit($produit)]);
    }

    /**
     * Supprime l'offre d'un produit.
     */
    #[Route('/{id}/offre', name: 'api_produits_offre_delete', methods: ['DELETE'])]
    public function deleteOffre(int $id): JsonResponse
    {
        $boutique = $this->boutiqueAutorisee();
        $produit = $this->entityManager->getRepository(Produit::class)->find($id);
        if (!$boutique || !$produit || $produit->getBoutique()->getId() !== $boutique->getId()) {
            return $this->json(['error' => 'Produit introuvable ou non autorisé.'], Response::HTTP_FORBIDDEN);
        }
        if ($produit->getOffre()) {
            $this->entityManager->remove($produit->getOffre());
            $produit->setOffre(null);
            $this->entityManager->flush();
        }
        return $this->json(['supprime' => true]);
    }

    /**
     * @param array<int, mixed> $variantes
     */
    private function appliquerVariantes(Produit $produit, array $variantes): void
    {
        $vues = [];
        foreach ($variantes as $v) {
            $couleur = trim((string) ($v['couleur'] ?? ''));
            $taille = trim((string) ($v['taille'] ?? ''));
            if ($couleur === '' || $taille === '') {
                continue;
            }
            $cle = $couleur . '||' . $taille;
            if (isset($vues[$cle])) {
                continue;
            }
            $vues[$cle] = true;

            $variante = new Variante();
            $variante->setCouleur($couleur);
            $variante->setTaille($taille);
            $variante->setStock(max(0, (int) ($v['stock'] ?? 0)));
            $produit->addVariante($variante);
        }
    }

    private function boutiqueAutorisee(): ?Boutique
    {
        $user = $this->getUser();
        if (!$user instanceof Vendeur) {
            return null;
        }
        return $user->getBoutiqueActive();
    }
}