=== WS Brevo Form Connector ===
Contributors: webstrategy
Tags: brevo, sendinblue, email marketing, crm, ajax
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Synchronise des contacts vers Brevo via un endpoint AJAX universel et une API PHP. Indépendant de tout plugin de formulaire.

== Description ==

WS Brevo Form Connector est une brique d'infrastructure : il expose un endpoint AJAX et une méthode PHP statique pour pousser des contacts vers Brevo depuis n'importe quelle source — formulaire custom, script JS, hook WordPress, autre plugin.

Le plugin ne dépend d'aucun builder de formulaires et n'en nécessite aucun.

**Endpoint AJAX universel**

Disponible pour les utilisateurs connectés et non connectés (wp_ajax + wp_ajax_nopriv). Le nonce est injecté automatiquement en footer via l'objet JS `wsBrevoFCPublic`.

**API PHP**

Appelez `WS_Brevo_FC_Sync::contact()` directement depuis n'importe quel hook ou plugin tiers.

**Fonctionnalités**

* Endpoint AJAX priv + nopriv avec vérification de nonce
* Mapping des champs POST → attributs Brevo configurable en admin
* Règles par source : dirigez chaque origine vers une liste Brevo différente
* Journal des synchronisations (50 dernières entrées)
* Test de connexion API intégré
* Hook `ws_brevo_fc_after_sync` pour extensions tierces

== Installation ==

1. Activez le plugin depuis l'administration WordPress.
2. Rendez-vous dans **Brevo Connector** dans le menu.
3. Saisissez votre clé API Brevo (Brevo → Paramètres → Clés API & SMTP).
4. Renseignez l'ID de votre liste Brevo cible.
5. Configurez le mapping des champs si nécessaire.

== Frequently Asked Questions ==

= Quels plugins de formulaires sont supportés ? =

Aucun en particulier — et c'est voulu. Le plugin expose un endpoint AJAX et une méthode PHP que vous branchez sur la source de votre choix.

= Comment intégrer avec mon formulaire custom ? =

Utilisez l'endpoint AJAX documenté dans l'onglet "Endpoint AJAX" de la page de configuration, ou appelez `WS_Brevo_FC_Sync::contact()` directement en PHP.

= Comment trouver mon ID de liste Brevo ? =

Dans Brevo, allez dans Contacts → Listes, cliquez sur votre liste. L'ID est visible dans l'URL.

= Comment désactiver la sync pour une source précise ? =

Dans l'onglet "Règles par source", ajoutez une règle avec l'identifiant de la source (visible dans le journal) et désactivez le toggle.

== Changelog ==

= 1.2.0 =
* Suppression de tous les adaptateurs spécifiques aux builders de formulaires
* Plugin entièrement indépendant de tout plugin tiers
* Simplification de l'architecture : endpoint AJAX + API PHP uniquement

= 1.1.0 =
* Ajout endpoint AJAX universel (wp_ajax + wp_ajax_nopriv)
* Nonce public injecté automatiquement en wp_footer
* Classe WS_Brevo_FC_Sync statique — point d'entrée unique
* Hook post-sync ws_brevo_fc_after_sync

= 1.0.0 =
* Version initiale
