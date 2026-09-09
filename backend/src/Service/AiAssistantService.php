<?php

namespace App\Service;

class AiAssistantService implements AiAssistantServiceInterface
{
    private const FAQ = [
        'fraîcheur' => 'L\'indice de fraîcheur est calculé à partir de la date de récolte et de la durée de conservation. Il peut être "très frais" (≤2 jours), "frais" (≤5 jours), "à consommer rapidement" (≤7 jours) ou "périmé".',
        'indice fraîcheur' => 'L\'indice de fraîcheur est calculé à partir de la date de récolte et de la durée de conservation. Il peut être "très frais" (≤2 jours), "frais" (≤5 jours), "à consommer rapidement" (≤7 jours) ou "périmé".',
        'livraison' => 'Une livraison est créée après paiement d\'une commande avec mode "livraison". Les livreurs disponibles peuvent l\'accepter via la liste des missions disponibles. L\'acheteur peut aussi choisir un livreur spécifique.',
        'livreur' => 'Un livreur peut voir les missions disponibles sur son tableau de bord. Il accepte une mission, confirme le retrait (photo), puis la livraison finale (photo). Les frais de livraison lui sont crédités sur son solde.',
        'paiement' => 'Le paiement se fait via Mobile Money (Orange Money / MTN MoMo). Le statut est vérifié indépendamment par le serveur — jamais depuis le navigateur.',
        'mobile money' => 'Le paiement se fait via Mobile Money (Orange Money / MTN MoMo). Le statut est vérifié indépendamment par le serveur — jamais depuis le navigateur.',
        'commande' => 'Une commande passe par les statuts : en_attente → confirmée → payée → en_cours (livraison) → livrée. L\'acheteur suit l\'évolution depuis "Mes commandes".',
        'statut' => 'Les statuts possibles : en_attente (en attente de confirmation), confirmée (le producteur a confirmé), payée (paiement reçu), en_cours (en livraison), livrée (terminée), annulée.',
        'solde' => 'Votre solde est votre portefeuille sur TerraLink. Les acheteurs peuvent le recharger via Mobile Money. Les producteurs et livreurs sont crédités automatiquement à la livraison.',
        'qr code' => 'Chaque lot possède un QR code unique qui permet de tracer le produit : origine, producteur, date de récolte, indice de fraîcheur et prix.',
        'carte' => 'La carte interactive montre les producteurs et leurs lots disponibles, ainsi que les livreurs actifs. Chaque catégorie de produit a son propre emoji.',
        'contact' => 'Pour toute assistance, contactez l\'équipe TerraLink via l\'interface d\'administration ou par email.',
    ];

    public function answer(string $question): string
    {
        $normalized = mb_strtolower(trim($question));
        $normalized = str_replace(['?', '!', '.', ','], '', $normalized);

        foreach (self::FAQ as $keyword => $response) {
            if (str_contains($normalized, $keyword)) {
                return $response;
            }
        }

        return 'Je suis l\'assistant TerraLink. Je peux vous renseigner sur : les statuts de commande, la livraison, les paiements Mobile Money, le solde, les QR codes, la carte interactive, et l\'indice de fraîcheur. Posez votre question !';
    }
}