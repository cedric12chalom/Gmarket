<?php

namespace App\Command;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\Variete;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed:catalogue',
    description: 'Create 30 categories and 100 products in the database.'
)]
class SeedCatalogueCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categoryDefinitions = $this->getCategoryDefinitions();
        $categoryRepository = $this->entityManager->getRepository(Categorie::class);
        $categories = [];
        $createdCategories = 0;

        foreach ($categoryDefinitions as $definition) {
            $categorie = $categoryRepository->findOneBy(['nom' => $definition['nom']]);
            if (!$categorie) {
                $categorie = new Categorie();
                $categorie->setNom($definition['nom']);
                $categorie->setTypeTransport($definition['type_transport']);
                $categorie->setCommissionFixe($definition['commission_fixe']);
                $categorie->setCommissionPourcent($definition['commission_pourcent']);
                $categorie->setApprouve($definition['approuve']);
                $this->entityManager->persist($categorie);
                $createdCategories++;
            } else {
                $categorie->setTypeTransport($definition['type_transport']);
                $categorie->setCommissionFixe($definition['commission_fixe']);
                $categorie->setCommissionPourcent($definition['commission_pourcent']);
                $categorie->setApprouve($definition['approuve']);
            }

            $categories[$definition['nom']] = $categorie;
        }

        $this->entityManager->flush();

        $productDefinitions = $this->getProductDefinitions();
        $productRepository = $this->entityManager->getRepository(Produit::class);
        $createdProducts = 0;

        foreach ($productDefinitions as $definition) {
            $produit = $productRepository->findOneBy(['nom' => $definition['nom']]);
            $categorie = $categories[$definition['categorie_nom']] ?? null;
            if (!$categorie instanceof Categorie) {
                continue;
            }

            if ($produit) {
                // Backfill: existing seed products created before photos existed get one,
                // but never overwrite a photo that was manually set afterwards. Products
                // still on the generic category default are upgraded to a product photo.
                $photoParCategorie = $this->getCategoriePhotoMap();
                $photoActuelle = $produit->getPhoto();
                if (
                    $definition['photo'] !== null
                    && $photoActuelle !== $definition['photo']
                    && ($photoActuelle === null || $photoActuelle === ($photoParCategorie[$definition['categorie_nom']] ?? null))
                ) {
                    $produit->setPhoto($definition['photo']);
                    $this->entityManager->persist($produit);
                }
                continue;
            }

            $produit = new Produit();
            $produit->setNom($definition['nom']);
            $produit->setDescription($definition['description']);
            $produit->setUnite($definition['unite']);
            $produit->setPrixMin($definition['prix_min']);
            $produit->setPrixMax($definition['prix_max']);
            $produit->setPhoto($definition['photo']);
            $produit->setCategorie($categorie);

            $this->entityManager->persist($produit);
            $createdProducts++;
        }

        $this->entityManager->flush();

        $createdVarieties = $this->ensureProductVarieties();

        $totalCategories = $categoryRepository->count([]);
        $totalProducts = $productRepository->count([]);

        $output->writeln(sprintf('Seed complete. Categories created or updated: %d.', $createdCategories));
        $output->writeln(sprintf('Products created: %d.', $createdProducts));
        $output->writeln(sprintf('Varieties created: %d.', $createdVarieties));
        $output->writeln(sprintf('Total categories now: %d.', $totalCategories));
        $output->writeln(sprintf('Total products now: %d.', $totalProducts));
        $output->writeln('Run the application after seeding to verify the catalogue.');

        return Command::SUCCESS;
    }

    private function ensureProductVarieties(): int
    {
        $produitRepository = $this->entityManager->getRepository(Produit::class);
        $varieteRepository = $this->entityManager->getRepository(Variete::class);
        $created = 0;

        foreach ($this->getVarietyDefinitions() as $produitNom => $varieteNoms) {
            $produit = $produitRepository->findOneBy(['nom' => $produitNom]);
            if (!$produit instanceof Produit) {
                continue;
            }

            foreach ($varieteNoms as $varieteNom) {
                $existing = $varieteRepository->findOneBy([
                    'produit' => $produit->getId(),
                    'nom' => $varieteNom,
                ]);
                if ($existing instanceof Variete) {
                    continue;
                }

                $variete = new Variete();
                $variete->setProduit($produit);
                $variete->setNom($varieteNom);
                $this->entityManager->persist($variete);
                $created++;
            }
        }

        $this->entityManager->flush();
        return $created;
    }

    private function getVarietyDefinitions(): array
    {
        return [
            'Mangues' => ['Kent', 'Keitt', 'Tommy Atkins', 'Amelie', 'Palmer', 'Julie'],
            'Bananes' => ['Cavendish', 'Gros Michel', 'Poyo', 'Figue sucree', 'French', 'Faux-corne', 'Vrai-corne', 'Batard', 'Essong', 'Ekon', 'Ovang', 'Zue', 'Big Ebanga'],
            'Papayes' => ['Solo', 'Sunrise Solo', 'Red Lady', 'Maradol'],
            'Ananas' => ['Cayenne lisse', 'MD2', 'Queen Victoria', 'Sugar Loaf'],
        ];
    }

    private function getCategoryDefinitions(): array
    {
        return [
            ['nom' => 'Fruits', 'type_transport' => 'rapide', 'commission_fixe' => '2.00', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Légumes', 'type_transport' => 'rapide', 'commission_fixe' => '1.50', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Céréales', 'type_transport' => 'standard', 'commission_fixe' => '1.00', 'commission_pourcent' => '3.00', 'approuve' => true],
            ['nom' => 'Légumineuses', 'type_transport' => 'standard', 'commission_fixe' => '1.20', 'commission_pourcent' => '3.50', 'approuve' => true],
            ['nom' => 'Épices', 'type_transport' => 'standard', 'commission_fixe' => '2.50', 'commission_pourcent' => '7.00', 'approuve' => true],
            ['nom' => 'Tubercules', 'type_transport' => 'rapide', 'commission_fixe' => '1.50', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Oléagineux', 'type_transport' => 'standard', 'commission_fixe' => '1.80', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Produits laitiers', 'type_transport' => 'refrigere', 'commission_fixe' => '4.00', 'commission_pourcent' => '6.00', 'approuve' => true],
            ['nom' => 'Viandes', 'type_transport' => 'refrigere', 'commission_fixe' => '5.00', 'commission_pourcent' => '6.00', 'approuve' => true],
            ['nom' => 'Poissons', 'type_transport' => 'refrigere', 'commission_fixe' => '4.50', 'commission_pourcent' => '6.50', 'approuve' => true],
            ['nom' => 'Herbes aromatiques', 'type_transport' => 'rapide', 'commission_fixe' => '1.20', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Champignons', 'type_transport' => 'rapide', 'commission_fixe' => '1.80', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Fruits secs', 'type_transport' => 'standard', 'commission_fixe' => '2.20', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Jus et nectars', 'type_transport' => 'rapide', 'commission_fixe' => '2.50', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Plantes médicinales', 'type_transport' => 'rapide', 'commission_fixe' => '2.00', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Farines', 'type_transport' => 'standard', 'commission_fixe' => '1.50', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Huiles et condiments', 'type_transport' => 'standard', 'commission_fixe' => '2.60', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Produits transformés', 'type_transport' => 'standard', 'commission_fixe' => '2.20', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Semences', 'type_transport' => 'standard', 'commission_fixe' => '1.30', 'commission_pourcent' => '3.00', 'approuve' => true],
            ['nom' => 'Pains et viennoiseries', 'type_transport' => 'rapide', 'commission_fixe' => '1.50', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Miel', 'type_transport' => 'standard', 'commission_fixe' => '3.00', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Œufs', 'type_transport' => 'rapide', 'commission_fixe' => '2.00', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Fleurs comestibles', 'type_transport' => 'rapide', 'commission_fixe' => '2.20', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Sucre et miel', 'type_transport' => 'standard', 'commission_fixe' => '1.80', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Conserves', 'type_transport' => 'standard', 'commission_fixe' => '2.00', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Boissons traditionnelles', 'type_transport' => 'rapide', 'commission_fixe' => '1.80', 'commission_pourcent' => '4.00', 'approuve' => true],
            ['nom' => 'Fourrages', 'type_transport' => 'standard', 'commission_fixe' => '1.20', 'commission_pourcent' => '3.00', 'approuve' => true],
            ['nom' => 'Bois énergie', 'type_transport' => 'standard', 'commission_fixe' => '1.00', 'commission_pourcent' => '2.00', 'approuve' => true],
            ['nom' => 'Cuirs et peaux', 'type_transport' => 'standard', 'commission_fixe' => '2.50', 'commission_pourcent' => '5.00', 'approuve' => true],
            ['nom' => 'Emballages artisanaux', 'type_transport' => 'standard', 'commission_fixe' => '1.50', 'commission_pourcent' => '4.00', 'approuve' => true],
        ];
    }

    private function getProductDefinitions(): array
    {
        $definitions = [
            ['nom' => 'Bananes', 'categorie_nom' => 'Fruits', 'description' => 'Bananes jaunes sucrées, cueillies à maturité.', 'unite' => 'kg', 'prix_min' => '300.00', 'prix_max' => '450.00', 'photo' => null],            ['nom' => 'Mangues', 'categorie_nom' => 'Fruits', 'description' => 'Mangues fraîches et juteuses cultivées localement.', 'unite' => 'kg', 'prix_min' => '400.00', 'prix_max' => '550.00', 'photo' => null],
            ['nom' => 'Papayes', 'categorie_nom' => 'Fruits', 'description' => 'Papayes douces, parfaites pour jus et desserts.', 'unite' => 'kg', 'prix_min' => '500.00', 'prix_max' => '700.00', 'photo' => null],
            ['nom' => 'Ananas', 'categorie_nom' => 'Fruits', 'description' => 'Ananas aromatiques cultivés en plein champ.', 'unite' => 'kg', 'prix_min' => '600.00', 'prix_max' => '800.00', 'photo' => null],
            ['nom' => 'Tomates', 'categorie_nom' => 'Légumes', 'description' => 'Tomates mûres idéales pour salades et sauces.', 'unite' => 'kg', 'prix_min' => '250.00', 'prix_max' => '400.00', 'photo' => null],
            ['nom' => 'Concombres', 'categorie_nom' => 'Légumes', 'description' => 'Concombres croquants et rafraîchissants.', 'unite' => 'kg', 'prix_min' => '200.00', 'prix_max' => '350.00', 'photo' => null],
            ['nom' => 'Carottes', 'categorie_nom' => 'Légumes', 'description' => 'Carottes croquantes et riches en vitamines.', 'unite' => 'kg', 'prix_min' => '180.00', 'prix_max' => '300.00', 'photo' => null],
            ['nom' => 'Poivrons', 'categorie_nom' => 'Légumes', 'description' => 'Poivrons colorés pleins de saveur.', 'unite' => 'kg', 'prix_min' => '220.00', 'prix_max' => '380.00', 'photo' => null],
            ['nom' => 'Maïs', 'categorie_nom' => 'Céréales', 'description' => 'Maïs local idéal pour cuisine traditionnelle.', 'unite' => 'kg', 'prix_min' => '150.00', 'prix_max' => '230.00', 'photo' => null],
            ['nom' => 'Mil', 'categorie_nom' => 'Céréales', 'description' => 'Mil nourrissant adapté aux plats africains.', 'unite' => 'kg', 'prix_min' => '140.00', 'prix_max' => '210.00', 'photo' => null],
            ['nom' => 'Sorgho', 'categorie_nom' => 'Céréales', 'description' => 'Sorgho sain et facile à cuisiner.', 'unite' => 'kg', 'prix_min' => '130.00', 'prix_max' => '200.00', 'photo' => null],
            ['nom' => 'Riz', 'categorie_nom' => 'Céréales', 'description' => 'Riz blanc de qualité pour repas quotidiens.', 'unite' => 'kg', 'prix_min' => '160.00', 'prix_max' => '240.00', 'photo' => null],
            ['nom' => 'Haricots rouges', 'categorie_nom' => 'Légumineuses', 'description' => 'Haricots rouges riches en fibres.', 'unite' => 'kg', 'prix_min' => '220.00', 'prix_max' => '330.00', 'photo' => null],
            ['nom' => 'Pois chiches', 'categorie_nom' => 'Légumineuses', 'description' => 'Pois chiches fermes, parfaits pour mijotés.', 'unite' => 'kg', 'prix_min' => '240.00', 'prix_max' => '360.00', 'photo' => null],
            ['nom' => 'Lentilles', 'categorie_nom' => 'Légumineuses', 'description' => 'Lentilles nutritives pour soupes et plats.', 'unite' => 'kg', 'prix_min' => '180.00', 'prix_max' => '270.00', 'photo' => null],
            ['nom' => 'Niébé', 'categorie_nom' => 'Légumineuses', 'description' => 'Niébé traditionnel, riche en protéines.', 'unite' => 'kg', 'prix_min' => '210.00', 'prix_max' => '310.00', 'photo' => null],
            ['nom' => 'Poivre noir', 'categorie_nom' => 'Épices', 'description' => 'Poivre noir parfumé pour relever vos plats.', 'unite' => 'kg', 'prix_min' => '800.00', 'prix_max' => '1200.00', 'photo' => null],
            ['nom' => 'Gingembre', 'categorie_nom' => 'Épices', 'description' => 'Gingembre frais et piquant.', 'unite' => 'kg', 'prix_min' => '700.00', 'prix_max' => '1000.00', 'photo' => null],
            ['nom' => 'Cumin', 'categorie_nom' => 'Épices', 'description' => 'Cumin aromatique pour sauces et marinades.', 'unite' => 'kg', 'prix_min' => '650.00', 'prix_max' => '950.00', 'photo' => null],
            ['nom' => 'Curcuma', 'categorie_nom' => 'Épices', 'description' => 'Curcuma riche en arômes et bienfaits.', 'unite' => 'kg', 'prix_min' => '700.00', 'prix_max' => '1100.00', 'photo' => null],
            ['nom' => 'Igname', 'categorie_nom' => 'Tubercules', 'description' => 'Igname tendre, parfait pour la cuisson.', 'unite' => 'kg', 'prix_min' => '180.00', 'prix_max' => '260.00', 'photo' => null],
            ['nom' => 'Patate douce', 'categorie_nom' => 'Tubercules', 'description' => 'Patate douce sucrée et nutritive.', 'unite' => 'kg', 'prix_min' => '200.00', 'prix_max' => '300.00', 'photo' => null],
            ['nom' => 'Pomme de terre', 'categorie_nom' => 'Tubercules', 'description' => 'Pommes de terre polyvalentes.', 'unite' => 'kg', 'prix_min' => '150.00', 'prix_max' => '230.00', 'photo' => null],
            ['nom' => 'Taro', 'categorie_nom' => 'Tubercules', 'description' => 'Taro tendre pour plats traditionnels.', 'unite' => 'kg', 'prix_min' => '190.00', 'prix_max' => '280.00', 'photo' => null],
            ['nom' => 'Arachides', 'categorie_nom' => 'Oléagineux', 'description' => 'Arachides fraîches, idéales pour snacking.', 'unite' => 'kg', 'prix_min' => '500.00', 'prix_max' => '700.00', 'photo' => null],
            ['nom' => 'Noix de cajou', 'categorie_nom' => 'Oléagineux', 'description' => 'Noix de cajou grillées et croustillantes.', 'unite' => 'kg', 'prix_min' => '1200.00', 'prix_max' => '1600.00', 'photo' => null],
            ['nom' => 'Sésame', 'categorie_nom' => 'Oléagineux', 'description' => 'Graines de sésame dorées et parfumées.', 'unite' => 'kg', 'prix_min' => '900.00', 'prix_max' => '1300.00', 'photo' => null],
            ['nom' => 'Tournesol', 'categorie_nom' => 'Oléagineux', 'description' => 'Graines de tournesol pour cuisson et pâtisserie.', 'unite' => 'kg', 'prix_min' => '450.00', 'prix_max' => '650.00', 'photo' => null],
            ['nom' => 'Lait frais', 'categorie_nom' => 'Produits laitiers', 'description' => 'Lait frais entier provenant de la ferme.', 'unite' => 'litre', 'prix_min' => '550.00', 'prix_max' => '750.00', 'photo' => null],
            ['nom' => 'Beurre', 'categorie_nom' => 'Produits laitiers', 'description' => 'Beurre artisanal riche en goût.', 'unite' => 'kg', 'prix_min' => '800.00', 'prix_max' => '1100.00', 'photo' => null],
            ['nom' => 'Fromage frais', 'categorie_nom' => 'Produits laitiers', 'description' => 'Fromage frais crémeux pour tartines.', 'unite' => 'kg', 'prix_min' => '650.00', 'prix_max' => '900.00', 'photo' => null],
            ['nom' => 'Yaourt', 'categorie_nom' => 'Produits laitiers', 'description' => 'Yaourt nature fabriqué localement.', 'unite' => 'kg', 'prix_min' => '520.00', 'prix_max' => '760.00', 'photo' => null],
            ['nom' => 'Poulet entier', 'categorie_nom' => 'Viandes', 'description' => 'Poulet entier élevé en plein air.', 'unite' => 'kg', 'prix_min' => '1200.00', 'prix_max' => '1800.00', 'photo' => null],
            ['nom' => 'Bœuf haché', 'categorie_nom' => 'Viandes', 'description' => 'Bœuf haché frais, de qualité supérieure.', 'unite' => 'kg', 'prix_min' => '1800.00', 'prix_max' => '2400.00', 'photo' => null],
            ['nom' => 'Porc fumé', 'categorie_nom' => 'Viandes', 'description' => 'Porc fumé prêt à cuire.', 'unite' => 'kg', 'prix_min' => '1600.00', 'prix_max' => '2200.00', 'photo' => null],
            ['nom' => 'Agneau', 'categorie_nom' => 'Viandes', 'description' => 'Agneau tendre et savoureux.', 'unite' => 'kg', 'prix_min' => '2000.00', 'prix_max' => '2800.00', 'photo' => null],
            ['nom' => 'Tilapia', 'categorie_nom' => 'Poissons', 'description' => 'Tilapia frais capturé localement.', 'unite' => 'kg', 'prix_min' => '950.00', 'prix_max' => '1300.00', 'photo' => null],
            ['nom' => 'Maquereau', 'categorie_nom' => 'Poissons', 'description' => 'Maquereau riche en oméga-3.', 'unite' => 'kg', 'prix_min' => '800.00', 'prix_max' => '1100.00', 'photo' => null],
            ['nom' => 'Sardines', 'categorie_nom' => 'Poissons', 'description' => 'Sardines fraîches et savoureuses.', 'unite' => 'kg', 'prix_min' => '700.00', 'prix_max' => '980.00', 'photo' => null],
            ['nom' => 'Crevettes', 'categorie_nom' => 'Poissons', 'description' => 'Crevettes de rivière, prêtes à cuire.', 'unite' => 'kg', 'prix_min' => '1500.00', 'prix_max' => '2000.00', 'photo' => null],
            ['nom' => 'Basilic', 'categorie_nom' => 'Herbes aromatiques', 'description' => 'Basilic frais à la saveur intense.', 'unite' => 'botte', 'prix_min' => '200.00', 'prix_max' => '300.00', 'photo' => null],
            ['nom' => 'Persil', 'categorie_nom' => 'Herbes aromatiques', 'description' => 'Persil vert et parfumé.', 'unite' => 'botte', 'prix_min' => '180.00', 'prix_max' => '250.00', 'photo' => null],
            ['nom' => 'Menthe', 'categorie_nom' => 'Herbes aromatiques', 'description' => 'Menthe fraîche pour boissons et cuisine.', 'unite' => 'botte', 'prix_min' => '150.00', 'prix_max' => '220.00', 'photo' => null],
            ['nom' => 'Coriandre', 'categorie_nom' => 'Herbes aromatiques', 'description' => 'Coriandre aromatique, idéale en sauce.', 'unite' => 'botte', 'prix_min' => '180.00', 'prix_max' => '260.00', 'photo' => null],
            ['nom' => 'Champignons de Paris', 'categorie_nom' => 'Champignons', 'description' => 'Champignons de Paris frais.', 'unite' => 'kg', 'prix_min' => '700.00', 'prix_max' => '950.00', 'photo' => null],
            ['nom' => 'Pleurotes', 'categorie_nom' => 'Champignons', 'description' => 'Pleurotes tendres et savoureux.', 'unite' => 'kg', 'prix_min' => '850.00', 'prix_max' => '1200.00', 'photo' => null],
            ['nom' => 'Champignons sauvages', 'categorie_nom' => 'Champignons', 'description' => 'Champignons sauvages parfumés.', 'unite' => 'kg', 'prix_min' => '900.00', 'prix_max' => '1300.00', 'photo' => null],
            ['nom' => 'Morilles', 'categorie_nom' => 'Champignons', 'description' => 'Morilles rares et aromatiques.', 'unite' => 'kg', 'prix_min' => '1200.00', 'prix_max' => '1700.00', 'photo' => null],
            ['nom' => 'Noix', 'categorie_nom' => 'Fruits secs', 'description' => 'Noix croquantes et riches en huile.', 'unite' => 'kg', 'prix_min' => '1000.00', 'prix_max' => '1400.00', 'photo' => null],
            ['nom' => 'Amandes', 'categorie_nom' => 'Fruits secs', 'description' => 'Amandes fraîches pour snacking.', 'unite' => 'kg', 'prix_min' => '1400.00', 'prix_max' => '2000.00', 'photo' => null],
            ['nom' => 'Raisins secs', 'categorie_nom' => 'Fruits secs', 'description' => 'Raisins secs sucrés et moelleux.', 'unite' => 'kg', 'prix_min' => '900.00', 'prix_max' => '1200.00', 'photo' => null],
            ['nom' => 'Fruits secs mélangés', 'categorie_nom' => 'Fruits secs', 'description' => 'Mélange de fruits secs assortis.', 'unite' => 'kg', 'prix_min' => '1200.00', 'prix_max' => '1700.00', 'photo' => null],
            ['nom' => 'Jus d\'orange', 'categorie_nom' => 'Jus et nectars', 'description' => 'Jus d\'orange pressé localement.', 'unite' => 'litre', 'prix_min' => '700.00', 'prix_max' => '950.00', 'photo' => null],
            ['nom' => 'Jus de mangue', 'categorie_nom' => 'Jus et nectars', 'description' => 'Jus de mangue naturel et sucré.', 'unite' => 'litre', 'prix_min' => '800.00', 'prix_max' => '1100.00', 'photo' => null],
            ['nom' => 'Nectar de bissap', 'categorie_nom' => 'Jus et nectars', 'description' => 'Nectar de bissap frais et fruité.', 'unite' => 'litre', 'prix_min' => '850.00', 'prix_max' => '1200.00', 'photo' => null],
            ['nom' => 'Jus de gingembre', 'categorie_nom' => 'Jus et nectars', 'description' => 'Jus de gingembre piquant et rafraîchissant.', 'unite' => 'litre', 'prix_min' => '750.00', 'prix_max' => '1000.00', 'photo' => null],
            ['nom' => 'Feuilles de moringa', 'categorie_nom' => 'Plantes médicinales', 'description' => 'Feuilles de moringa riches en nutriments.', 'unite' => 'botte', 'prix_min' => '180.00', 'prix_max' => '260.00', 'photo' => null],
            ['nom' => 'Graine de fenugrec', 'categorie_nom' => 'Plantes médicinales', 'description' => 'Graine de fenugrec aux vertus digestives.', 'unite' => 'kg', 'prix_min' => '250.00', 'prix_max' => '340.00', 'photo' => null],
            ['nom' => 'Feuilles de neem', 'categorie_nom' => 'Plantes médicinales', 'description' => 'Feuilles de neem pour infusion bien-être.', 'unite' => 'botte', 'prix_min' => '200.00', 'prix_max' => '290.00', 'photo' => null],
            ['nom' => 'Feuilles de kinkeliba', 'categorie_nom' => 'Plantes médicinales', 'description' => 'Feuilles de kinkeliba pour infusion traditionnelle.', 'unite' => 'botte', 'prix_min' => '220.00', 'prix_max' => '310.00', 'photo' => null],
            ['nom' => 'Farine de maïs', 'categorie_nom' => 'Farines', 'description' => 'Farine de maïs locale fraîchement moulue.', 'unite' => 'kg', 'prix_min' => '300.00', 'prix_max' => '450.00', 'photo' => null],
            ['nom' => 'Farine de manioc', 'categorie_nom' => 'Farines', 'description' => 'Farine de manioc idéale pour recettes traditionnelles.', 'unite' => 'kg', 'prix_min' => '320.00', 'prix_max' => '480.00', 'photo' => null],
            ['nom' => 'Farine de fonio', 'categorie_nom' => 'Farines', 'description' => 'Farine de fonio saine et légère.', 'unite' => 'kg', 'prix_min' => '380.00', 'prix_max' => '560.00', 'photo' => null],
            ['nom' => 'Farine de sorgho', 'categorie_nom' => 'Farines', 'description' => 'Farine de sorgho gourmande et nutritive.', 'unite' => 'kg', 'prix_min' => '340.00', 'prix_max' => '500.00', 'photo' => null],
            ['nom' => 'Huile d\'arachide', 'categorie_nom' => 'Huiles et condiments', 'description' => 'Huile d\'arachide pure pour cuisson.', 'unite' => 'litre', 'prix_min' => '1200.00', 'prix_max' => '1600.00', 'photo' => null],
            ['nom' => 'Sel rose', 'categorie_nom' => 'Huiles et condiments', 'description' => 'Sel rose naturel pour assaisonnement.', 'unite' => 'kg', 'prix_min' => '200.00', 'prix_max' => '300.00', 'photo' => null],
            ['nom' => 'Pâte d\'arachide', 'categorie_nom' => 'Huiles et condiments', 'description' => 'Pâte d\'arachide onctueuse pour sauces.', 'unite' => 'kg', 'prix_min' => '900.00', 'prix_max' => '1200.00', 'photo' => null],
            ['nom' => 'Huile de palme', 'categorie_nom' => 'Huiles et condiments', 'description' => 'Huile de palme traditionnelle pour cuisson.', 'unite' => 'litre', 'prix_min' => '850.00', 'prix_max' => '1150.00', 'photo' => null],
            ['nom' => 'Purée de tomate', 'categorie_nom' => 'Produits transformés', 'description' => 'Purée de tomate artisanale, sans conservateur.', 'unite' => 'kg', 'prix_min' => '450.00', 'prix_max' => '620.00', 'photo' => null],
            ['nom' => 'Sauce pimentée', 'categorie_nom' => 'Produits transformés', 'description' => 'Sauce pimentée maison aux arômes intenses.', 'unite' => 'pot', 'prix_min' => '420.00', 'prix_max' => '600.00', 'photo' => null],
            ['nom' => 'Confiture de mangue', 'categorie_nom' => 'Produits transformés', 'description' => 'Confiture de mangue sucrée naturellement.', 'unite' => 'pot', 'prix_min' => '480.00', 'prix_max' => '720.00', 'photo' => null],
            ['nom' => 'Beurre de cacahuète', 'categorie_nom' => 'Produits transformés', 'description' => 'Beurre de cacahuète crémeux et nutritif.', 'unite' => 'pot', 'prix_min' => '900.00', 'prix_max' => '1200.00', 'photo' => null],
            ['nom' => 'Semences de maïs', 'categorie_nom' => 'Semences', 'description' => 'Semences de maïs sélectionnées pour germination.', 'unite' => 'kg', 'prix_min' => '180.00', 'prix_max' => '260.00', 'photo' => null],
            ['nom' => 'Semences de haricot', 'categorie_nom' => 'Semences', 'description' => 'Semences de haricot de qualité agricole.', 'unite' => 'kg', 'prix_min' => '200.00', 'prix_max' => '320.00', 'photo' => null],
            ['nom' => 'Semences de manioc', 'categorie_nom' => 'Semences', 'description' => 'Semences de manioc résistantes et productives.', 'unite' => 'kg', 'prix_min' => '170.00', 'prix_max' => '250.00', 'photo' => null],
            ['nom' => 'Semences de sorgho', 'categorie_nom' => 'Semences', 'description' => 'Semences de sorgho locales et robustes.', 'unite' => 'kg', 'prix_min' => '190.00', 'prix_max' => '280.00', 'photo' => null],
            ['nom' => 'Pain de mie', 'categorie_nom' => 'Pains et viennoiseries', 'description' => 'Pain de mie moelleux pour sandwiches.', 'unite' => 'pièce', 'prix_min' => '250.00', 'prix_max' => '350.00', 'photo' => null],
            ['nom' => 'Beignets', 'categorie_nom' => 'Pains et viennoiseries', 'description' => 'Beignets chauds et croustillants.', 'unite' => 'pièce', 'prix_min' => '120.00', 'prix_max' => '180.00', 'photo' => null],
            ['nom' => 'Baguette', 'categorie_nom' => 'Pains et viennoiseries', 'description' => 'Baguette fraîche du jour.', 'unite' => 'pièce', 'prix_min' => '220.00', 'prix_max' => '320.00', 'photo' => null],
            ['nom' => 'Brioches', 'categorie_nom' => 'Pains et viennoiseries', 'description' => 'Brioches moelleuses et dorées.', 'unite' => 'pièce', 'prix_min' => '300.00', 'prix_max' => '420.00', 'photo' => null],
            ['nom' => 'Miel d\'acacia', 'categorie_nom' => 'Miel', 'description' => 'Miel d\'acacia léger et aromatique.', 'unite' => 'pot', 'prix_min' => '1200.00', 'prix_max' => '1700.00', 'photo' => null],
            ['nom' => 'Miel de fleurs sauvages', 'categorie_nom' => 'Miel', 'description' => 'Miel de fleurs sauvages riche en saveurs.', 'unite' => 'pot', 'prix_min' => '1300.00', 'prix_max' => '1800.00', 'photo' => null],
            ['nom' => 'Œufs de poule', 'categorie_nom' => 'Œufs', 'description' => 'Œufs de poule frais et de qualité.', 'unite' => 'douzaine', 'prix_min' => '350.00', 'prix_max' => '500.00', 'photo' => null],
            ['nom' => 'Œufs de cane', 'categorie_nom' => 'Œufs', 'description' => 'Œufs de cane nutritifs et frais.', 'unite' => 'douzaine', 'prix_min' => '450.00', 'prix_max' => '650.00', 'photo' => null],
            ['nom' => 'Fleurs d\'hibiscus', 'categorie_nom' => 'Fleurs comestibles', 'description' => 'Fleurs d\'hibiscus séchées pour infusion.', 'unite' => 'paquet', 'prix_min' => '220.00', 'prix_max' => '320.00', 'photo' => null],
            ['nom' => 'Pétales de rose', 'categorie_nom' => 'Fleurs comestibles', 'description' => 'Pétales de rose délicats et comestibles.', 'unite' => 'paquet', 'prix_min' => '250.00', 'prix_max' => '380.00', 'photo' => null],
            ['nom' => 'Sucre de canne', 'categorie_nom' => 'Sucre et miel', 'description' => 'Sucre de canne brut, non raffiné.', 'unite' => 'kg', 'prix_min' => '300.00', 'prix_max' => '420.00', 'photo' => null],
            ['nom' => 'Sirop de palme', 'categorie_nom' => 'Sucre et miel', 'description' => 'Sirop de palme artisanal et naturel.', 'unite' => 'litre', 'prix_min' => '280.00', 'prix_max' => '380.00', 'photo' => null],
            ['nom' => 'Conserves de poisson', 'categorie_nom' => 'Conserves', 'description' => 'Conserves de poisson prêtes à consommer.', 'unite' => 'boite', 'prix_min' => '500.00', 'prix_max' => '700.00', 'photo' => null],
            ['nom' => 'Conserves de légumes', 'categorie_nom' => 'Conserves', 'description' => 'Conserves de légumes variés pour plats rapides.', 'unite' => 'boite', 'prix_min' => '450.00', 'prix_max' => '650.00', 'photo' => null],
            ['nom' => 'Bissap', 'categorie_nom' => 'Boissons traditionnelles', 'description' => 'Boisson de bissap fruitée et rafraîchissante.', 'unite' => 'litre', 'prix_min' => '240.00', 'prix_max' => '360.00', 'photo' => null],
            ['nom' => 'Kinkeliba', 'categorie_nom' => 'Boissons traditionnelles', 'description' => 'Kinkeliba infusé pour une boisson chaleureuse.', 'unite' => 'litre', 'prix_min' => '220.00', 'prix_max' => '320.00', 'photo' => null],
            ['nom' => 'Foin', 'categorie_nom' => 'Fourrages', 'description' => 'Foin sec de qualité pour bétail.', 'unite' => 'sac', 'prix_min' => '1200.00', 'prix_max' => '1600.00', 'photo' => null],
            ['nom' => 'Aliment pour bétail', 'categorie_nom' => 'Fourrages', 'description' => 'Aliment concentré pour bétail.', 'unite' => 'sac', 'prix_min' => '800.00', 'prix_max' => '1100.00', 'photo' => null],
            ['nom' => 'Bois de chauffe', 'categorie_nom' => 'Bois énergie', 'description' => 'Bois de chauffe sec pour cuisson et chauffage.', 'unite' => 'stère', 'prix_min' => '1200.00', 'prix_max' => '1700.00', 'photo' => null],
            ['nom' => 'Charbon de bois', 'categorie_nom' => 'Bois énergie', 'description' => 'Charbon de bois prêt à l\'emploi.', 'unite' => 'sac', 'prix_min' => '600.00', 'prix_max' => '850.00', 'photo' => null],
            ['nom' => 'Cuir brut', 'categorie_nom' => 'Cuirs et peaux', 'description' => 'Cuir brut pour artisanat et maroquinerie.', 'unite' => 'pièce', 'prix_min' => '15000.00', 'prix_max' => '21000.00', 'photo' => null],
            ['nom' => 'Peau de vache', 'categorie_nom' => 'Cuirs et peaux', 'description' => 'Peau de vache tannées pour artisanat.', 'unite' => 'pièce', 'prix_min' => '10000.00', 'prix_max' => '15000.00', 'photo' => null],
            ['nom' => 'Sacs en osier', 'categorie_nom' => 'Emballages artisanaux', 'description' => 'Sacs en osier faits à la main.', 'unite' => 'pièce', 'prix_min' => '1800.00', 'prix_max' => '2400.00', 'photo' => null],
            ['nom' => 'Coffrets en bois', 'categorie_nom' => 'Emballages artisanaux', 'description' => 'Coffrets en bois élégants pour cadeaux.', 'unite' => 'pièce', 'prix_min' => '2800.00', 'prix_max' => '3600.00', 'photo' => null],
        ];

        // Fill each product with a real image URL: a dedicated product photo when
        // known (mangoes, bananas), otherwise one derived from its category.
        $photoByCategorie = $this->getCategoriePhotoMap();
        $photoByProduit = $this->getProduitPhotoMap();
        foreach ($definitions as &$definition) {
            $definition['photo'] = $photoByProduit[$definition['nom']] ?? $photoByCategorie[$definition['categorie_nom']] ?? $this->defaultPhoto();
        }
        unset($definition);

        return $definitions;
    }

    private function defaultPhoto(): string
    {
        return 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&q=80&fm=jpg&fit=crop';
    }

    private function getCategoriePhotoMap(): array
    {
        return [
            'Fruits' => 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?w=600&q=80&fm=jpg&fit=crop',
            'Légumes' => 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?w=600&q=80&fm=jpg&fit=crop',
            'Céréales' => 'https://images.unsplash.com/photo-1571748982800-fa51082c2224?w=600&q=80&fm=jpg&fit=crop',
            'Légumineuses' => 'https://images.unsplash.com/photo-1564894809611-1742fc40ed80?w=600&q=80&fm=jpg&fit=crop',
            'Épices' => 'https://images.unsplash.com/photo-1716816211590-c15a328a5ff0?w=600&q=80&fm=jpg&fit=crop',
            'Tubercules' => 'https://images.unsplash.com/photo-1757283961544-e161ac41b201?w=600&q=80&fm=jpg&fit=crop',
            'Oléagineux' => 'https://images.unsplash.com/photo-1600189020840-e9918c25269d?w=600&q=80&fm=jpg&fit=crop',
            'Produits laitiers' => 'https://images.unsplash.com/photo-1634141510639-d691d86f47be?w=600&q=80&fm=jpg&fit=crop',
            'Viandes' => 'https://images.unsplash.com/photo-1723893905879-0e309c2a8e06?w=600&q=80&fm=jpg&fit=crop',
            'Poissons' => 'https://images.unsplash.com/photo-1510130387422-82bed34b37e9?w=600&q=80&fm=jpg&fit=crop',
            'Herbes aromatiques' => 'https://images.unsplash.com/photo-1532091710512-26fd3b2dcf16?w=600&q=80&fm=jpg&fit=crop',
            'Champignons' => 'https://images.unsplash.com/photo-1571074635691-b910c7a5cdbb?w=600&q=80&fm=jpg&fit=crop',
            'Fruits secs' => 'https://images.unsplash.com/photo-1595412017587-b7f3117dff54?w=600&q=80&fm=jpg&fit=crop',
            'Jus et nectars' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=600&q=80&fm=jpg&fit=crop',
            'Plantes médicinales' => 'https://images.unsplash.com/photo-1514733670139-4d87a1941d55?w=600&q=80&fm=jpg&fit=crop',
            'Farines' => 'https://images.unsplash.com/photo-1610725664285-7c57e6eeac3f?w=600&q=80&fm=jpg&fit=crop',
            'Huiles et condiments' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600&q=80&fm=jpg&fit=crop',
            'Produits transformés' => 'https://images.unsplash.com/photo-1640348784724-93f7b14d8047?w=600&q=80&fm=jpg&fit=crop',
            'Semences' => 'https://images.unsplash.com/photo-1611843467160-25afb8df1074?w=600&q=80&fm=jpg&fit=crop',
            'Pains et viennoiseries' => 'https://images.unsplash.com/photo-1608198093002-ad4e005484ec?w=600&q=80&fm=jpg&fit=crop',
            'Miel' => 'https://images.unsplash.com/photo-1587049352851-8d4e89133924?w=600&q=80&fm=jpg&fit=crop',
            'Œufs' => 'https://images.unsplash.com/photo-1639194335563-d56b83f0060c?w=600&q=80&fm=jpg&fit=crop',
            'Fleurs comestibles' => 'https://images.unsplash.com/photo-1567990989224-6441e1483ac8?w=600&q=80&fm=jpg&fit=crop',
            'Sucre et miel' => 'https://images.unsplash.com/photo-1585155113372-6c1808141bf3?w=600&q=80&fm=jpg&fit=crop',
            'Conserves' => 'https://images.unsplash.com/photo-1640348784724-93f7b14d8047?w=600&q=80&fm=jpg&fit=crop',
            'Boissons traditionnelles' => 'https://images.unsplash.com/photo-1506802913710-40e2e66339c9?w=600&q=80&fm=jpg&fit=crop',
            'Fourrages' => 'https://images.unsplash.com/photo-1691002188941-baef50d5f661?w=600&q=80&fm=jpg&fit=crop',
            'Bois énergie' => 'https://images.unsplash.com/photo-1583248352195-d3a8e766edf2?w=600&q=80&fm=jpg&fit=crop',
            'Cuirs et peaux' => 'https://images.unsplash.com/photo-1571829604981-ea159f94e5ad?w=600&q=80&fm=jpg&fit=crop',
            'Emballages artisanaux' => 'https://images.unsplash.com/photo-1455669175216-9017c9b02fc6?w=600&q=80&fm=jpg&fit=crop',
        ];
    }

    private function getProduitPhotoMap(): array
    {
        return [
            'Mangues' => 'https://images.unsplash.com/photo-1553279768-865429fa0078?w=600&q=80&fm=jpg&fit=crop',
            'Bananes' => 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?w=600&q=80&fm=jpg&fit=crop',
        ];
    }
}
