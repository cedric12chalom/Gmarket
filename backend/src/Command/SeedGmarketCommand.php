<?php

namespace App\Command;

use App\Entity\Abonnement;
use App\Entity\Acheteur;
use App\Entity\Administrateur;
use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Offre;
use App\Entity\Produit;
use App\Entity\Variante;
use App\Entity\Vendeur;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed',
    description: 'Charge les données de démonstration Gmarket (catégories mode, abonnements, comptes, boutiques, produits, offres).'
)]
class SeedGmarketCommand extends Command
{
    public const DEMO_PASSWORD = 'Gmarket2026!';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->seedCategories($output);
        $this->seedAbonnements($output);
        $this->seedComptes($output);
        return Command::SUCCESS;
    }

    private function seedCategories(OutputInterface $output): void
    {
        $definitions = [
            ['Vêtements', 'vetements'],
            ['Chaussures', 'chaussures'],
            ['Chaussettes', 'chaussettes'],
            ['Sous-vêtements', 'sous-vetements'],
            ['Accessoires', 'accessoires'],
        ];
        $repo = $this->entityManager->getRepository(Categorie::class);
        foreach ($definitions as [$nom, $slug]) {
            if (!$repo->findOneBy(['slug' => $slug])) {
                $categorie = new Categorie();
                $categorie->setNom($nom);
                $categorie->setSlug($slug);
                $this->entityManager->persist($categorie);
            }
        }
        $this->entityManager->flush();
        $output->writeln('Catégories mode OK.');
    }

    private function seedAbonnements(OutputInterface $output): void
    {
        $definitions = [
            ['Essai gratuit', 'essai', 0.0, 30, 'Vente illimitée pendant 30 jours.'],
            ['Formule Essentielle', 'essentiel', 2500.0, 30, 'Vente illimitée, statistiques de base, badge boutique.'],
            ['Formule Pro', 'pro', 5000.0, 30, 'Vente illimitée, statistiques avancées, mise en avant, offre à durée limitée.'],
            ['Formule Premium', 'premium', 9000.0, 30, 'Tout Pro + visibilité maximale, support prioritaire, logo premium.'],
        ];
        $repo = $this->entityManager->getRepository(Abonnement::class);
        foreach ($definitions as [$nom, $code, $prix, $duree, $avantages]) {
            if (!$repo->findOneBy(['code' => $code])) {
                $abonnement = new Abonnement();
                $abonnement->setNom($nom);
                $abonnement->setCode($code);
                $abonnement->setPrix($prix);
                $abonnement->setDureeJours($duree);
                $abonnement->setAvantages($avantages);
                $this->entityManager->persist($abonnement);
            }
        }
        $this->entityManager->flush();
        $output->writeln(sprintf('Paliers d\'abonnement OK (essai: %d jours).', \App\Entity\Abonnement::ESSAI_JOURS));
    }

    private function seedComptes(OutputInterface $output): void
    {
        $repo = $this->entityManager->getRepository(\App\Entity\User::class);

        $admin = $repo->findOneBy(['email' => 'admin@gmarket.cm']);
        if (!$admin) {
            $admin = new Administrateur();
            $admin->setEmail('admin@gmarket.cm');
            $admin->setPassword($this->passwordHasher->hashPassword($admin, self::DEMO_PASSWORD));
            $admin->setNom('Administrateur Gmarket');
            $this->entityManager->persist($admin);
        }

        $acheteur = $repo->findOneBy(['email' => 'clara@gmarket.cm']);
        if (!$acheteur) {
            $acheteur = new Acheteur();
            $acheteur->setEmail('clara@gmarket.cm');
            $acheteur->setPassword($this->passwordHasher->hashPassword($acheteur, self::DEMO_PASSWORD));
            $acheteur->setRoles([Role::ACHETEUR->value]);
            $acheteur->setPrenom('Clara');
            $acheteur->setNom('Ngono');
            $acheteur->setTiktok('@clara.style');
            $acheteur->setInstagram('@clara.style');
            $acheteur->setPhoto('https://i.pravatar.cc/300');
            $this->entityManager->persist($acheteur);
        }

        $this->seedVendeurs($repo);
        $this->entityManager->flush();
        $output->writeln('Comptes de démo OK.');
    }

    private function seedVendeurs($repo): void
    {
        $definitions = [
            [
                'email' => 'dakar@sneakergm.cm', 'prenom' => 'Ibrahima', 'nom' => 'Diallo',
                'tiktok' => '@dakar.sneakers', 'instagram' => '@dakar.sneakers',
                'boutique' => ['nom' => 'Dakar Sneakers', 'theme' => 'sneakers', 'tiktokPseudo' => '@dakar.sneakers',
                    'description' => 'Sneakers authentiques livrées chez vous.', 'aLivreur' => true, 'livreurDetail' => 'Partenaire de livraison à Douala et Yaoundé.'],
                'produits' => [
                    ['nom' => 'Baskets Air Retro', 'prix' => 45000, 'categorie' => 'chaussures', 'colors' => ['Blanc', 'Noir'], 'tailles' => ['40', '41', '42', '43'], 'offre' => [36000, 3]],
                    ['nom' => 'Baskets Classic Low', 'prix' => 38000, 'categorie' => 'chaussures', 'colors' => ['Rouge'], 'tailles' => ['39', '40', '41'], 'offre' => null],
                ],
            ],
            [
                'email' => 'sweety@lingerie.cm', 'prenom' => 'Aïcha', 'nom' => 'Moukoko',
                'tiktok' => '@sweety.lingerie', 'instagram' => '@sweety.lingerie',
                'boutique' => ['nom' => 'Sweety Lingerie', 'theme' => 'lingerie', 'tiktokPseudo' => '@sweety.lingerie',
                    'description' => 'Lingerie élégante et confortable pour toutes les morphologies.', 'aLivreur' => false, 'livreurDetail' => null],
                'produits' => [
                    ['nom' => 'Ensemble dentelle douce', 'prix' => 12500, 'categorie' => 'sous-vetements', 'colors' => ['Rose', 'Noir'], 'tailles' => ['S', 'M', 'L'], 'offre' => [9750, 5]],
                    ['nom' => 'Culotte coton quotidien', 'prix' => 3500, 'categorie' => 'sous-vetements', 'colors' => ['Blanc', 'Beige'], 'tailles' => ['S', 'M', 'L', 'XL'], 'offre' => null],
                ],
            ],
            [
                'email' => 'lamodeafro@gmarket.cm', 'prenom' => 'Yannick', 'nom' => 'Tchoua',
                'tiktok' => '@la.mode.afro', 'facebook' => 'LaModeAfro',
                'boutique' => ['nom' => 'La Mode Afro', 'theme' => 'streetwear', 'tiktokPseudo' => '@la.mode.afro',
                    'description' => 'Vêtements streetwear inspirés de la culture africaine.', 'aLivreur' => true, 'livreurDetail' => 'Livreur à Bafoussam, Dschang et environs.'],
                'produits' => [
                    ['nom' => 'T-shirt Wax collection', 'prix' => 9000, 'categorie' => 'vetements', 'colors' => ['Jaune', 'Vert'], 'tailles' => ['S', 'M', 'L', 'XL'], 'offre' => null],
                    ['nom' => 'Hoodie urban heritage', 'prix' => 18000, 'categorie' => 'vetements', 'colors' => ['Noir'], 'tailles' => ['M', 'L', 'XL'], 'offre' => [14400, 2]],
                ],
            ],
            [
                'email' => 'shortstop@gmarket.cm', 'prenom' => 'Marie', 'nom' => 'Essomba',
                'tiktok' => '@marie.shortstop',
                'boutique' => ['nom' => 'Marie Shortstop', 'theme' => 'minimaliste', 'tiktokPseudo' => '@marie.shortstop',
                    'description' => 'Basiques minimalistes et accessoires mode.', 'aLivreur' => false, 'livreurDetail' => null],
                'produits' => [
                    ['nom' => 'Lot de chaussettes coton (3 paires)', 'prix' => 4500, 'categorie' => 'chaussettes', 'colors' => ['Blanc', 'Gris'], 'tailles' => ['39-41', '42-44'], 'offre' => null],
                    ['nom' => 'Foulard en soie', 'prix' => 7500, 'categorie' => 'accessoires', 'colors' => ['Bordeaux', 'Bleu nuit'], 'tailles' => ['Unique'], 'offre' => [5500, 10]],
                ],
            ],
        ];

        $repoBoutique = $this->entityManager->getRepository(Boutique::class);
        $repoProduit = $this->entityManager->getRepository(Produit::class);
        $repoCategorie = $this->entityManager->getRepository(Categorie::class);
        $repoAbonnement = $this->entityManager->getRepository(Abonnement::class);

        foreach ($definitions as $def) {
            $email = $def['email'];
            $vendeur = $this->entityManager->getRepository(Vendeur::class)->findOneBy(['email' => $email]);
            if (!$vendeur) {
                $vendeur = new Vendeur();
                $vendeur->setEmail($email);
                $vendeur->setPassword($this->passwordHasher->hashPassword($vendeur, self::DEMO_PASSWORD));
                $vendeur->setRoles([Role::VENDEUR->value]);
                $vendeur->setPrenom($def['prenom']);
                $vendeur->setNom($def['nom']);
                $vendeur->setTiktok($def['tiktok'] ?? null);
                $vendeur->setInstagram($def['instagram'] ?? null);
                $vendeur->setFacebook($def['facebook'] ?? null);
                $vendeur->setPhoto('https://i.pravatar.cc/300');
                $this->entityManager->persist($vendeur);
            }

            $boutiqueDef = $def['boutique'];
            $boutique = $repoBoutique->findOneBy(['slug' => strtolower(str_replace(' ', '-', $boutiqueDef['nom']))]);
            if (!$boutique) {
                $boutique = new Boutique();
                $boutique->setVendeur($vendeur);
                $boutique->setNom($boutiqueDef['nom']);
                $boutique->setSlug(strtolower(str_replace(' ', '-', $boutiqueDef['nom'])));
                $boutique->setDescription($boutiqueDef['description']);
                $boutique->setTheme($boutiqueDef['theme']);
                $boutique->setTiktokPseudo($boutiqueDef['tiktokPseudo']);
                $boutique->setALivreur($boutiqueDef['aLivreur']);
                $boutique->setLivreurDetail($boutiqueDef['livreurDetail']);
                $boutique->setLogo('https://placehold.co/200x200/1e293b/ffffff?text=' . urlencode(substr($boutiqueDef['nom'], 0, 1)));
                $boutique->setBanniere('https://placehold.co/1200x300/1e293b/facc15?text=' . urlencode($boutiqueDef['nom']));
                $this->entityManager->persist($boutique);
            }

            $this->seedProduits($vendeur, $boutique, $def['produits'], $repoProduit, $repoCategorie);
        }
    }

    private function seedProduits(Vendeur $vendeur, Boutique $boutique, array $produits, $repoProduit, $repoCategorie): void
    {
        foreach ($produits as $def) {
            $existant = $repoProduit->findOneBy(['nom' => $def['nom'], 'boutique' => $boutique->getId()]);
            if ($existant) {
                continue;
            }
            $categorie = $repoCategorie->findOneBy(['slug' => $def['categorie']]);
            if (!$categorie) {
                continue;
            }

            $produit = new Produit();
            $produit->setBoutique($boutique);
            $produit->setCategorie($categorie);
            $produit->setNom($def['nom']);
            $produit->setPrix((float) $def['prix']);
            $produit->setImages([
                'https://placehold.co/800x800/1e293b/facc15?text=' . urlencode($def['nom']),
                'https://placehold.co/800x800/0f172a/94a3b8?text=' . urlencode($def['nom']) . '+2',
            ]);
            $produit->setActif(true);
            $this->entityManager->persist($produit);

            foreach ($def['colors'] as $couleur) {
                foreach ($def['tailles'] as $taille) {
                    $variante = new Variante();
                    $variante->setProduit($produit);
                    $variante->setCouleur($couleur);
                    $variante->setTaille($taille);
                    $variante->setStock(random_int(5, 30));
                    $this->entityManager->persist($variante);
                    $produit->addVariante($variante);
                }
            }

            if ($def['offre']) {
                $offre = new Offre();
                $offre->setProduit($produit);
                $offre->setPrixPromo((float) $def['offre'][0]);
                $offre->setDateDebut(new \DateTimeImmutable());
                $offre->setDateFin((new \DateTimeImmutable())->modify('+' . $def['offre'][1] . ' days'));
                $this->entityManager->persist($offre);
                $produit->setOffre($offre);
            }
        }
        $this->entityManager->flush();
    }
}