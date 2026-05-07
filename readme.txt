=== WS Brevo Form Connector ===
Contributors: webstrategy
Tags: brevo, sendinblue, email marketing, crm, ajax
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connecteur universel Brevo pour WordPress. Synchronisez vos contacts via endpoint AJAX ou appel PHP direct, independamment de tout plugin de formulaire.

== Description ==

WS Brevo Form Connector expose un endpoint AJAX securise (utilisateurs connectes et non connectes) et une API PHP statique pour synchroniser n'importe quel contact dans votre base Brevo.

Le plugin est volontairement independant de tout plugin de formulaire. C'est a vous de l'appeler depuis votre code, votre formulaire HTML natif, ou n'importe quel evenement JavaScript.

**Fonctionnement**

L'objet JavaScript `wsBrevoFC` est automatiquement injecte sur toutes les pages frontend. Il contient l'URL AJAX, le nonce et le nom d'action.

Pour les developpeurs, `WS_Brevo_FC_Sync::contact()` est appelable directement depuis n'importe quel hook PHP.

**Fonctionnalites**

* Endpoint AJAX universel securise par nonce (connecte + non connecte)
* API PHP statique `WS_Brevo_FC_Sync::contact()` pour les integrations serveur
* Mapping des parametres vers les attributs Brevo (PRENOM, NOM, SMS, SOCIETE)
* Regles de routage par form_id vers des listes Brevo differentes
* Desactivation selective par form_id
* Journal des 50 dernieres synchronisations
* Test de connexion API integre

== Installation ==

1. Activez le plugin depuis l'administration WordPress.
2. Allez dans **Brevo Connector** dans le menu.
3. Saisissez votre cle API Brevo (Brevo -> Parametres -> Cles API & SMTP).
4. Renseignez l'ID de votre liste Brevo cible.
5. Integrez l'endpoint dans votre code (voir onglet Integration).

== Frequently Asked Questions ==

= Comment appeler l'endpoint depuis du JavaScript ? =

L'objet `wsBrevoFC` est disponible sur toutes les pages frontend. Envoyez une requete POST vers `wsBrevoFC.ajaxurl` avec `action`, `nonce`, `email`, et les champs optionnels `firstname`, `lastname`, `phone`, `company`, `list_id`, `form_id`.

= Comment appeler depuis du PHP ? =

`WS_Brevo_FC_Sync::contact( 'email@example.com', ['PRENOM' => 'Jean'], 3, 'mon-hook' );`

= A quoi sert le form_id ? =

Identifiant libre que vous choisissez pour chaque source d'appel. Il apparait dans le journal et permet de definir des regles de routage vers des listes Brevo differentes.

= Comment trouver mon ID de liste Brevo ? =

Dans Brevo -> Contacts -> Listes, cliquez sur votre liste. L'ID est dans l'URL.

== Changelog ==

= 1.3.0 =
* Suppression de toutes les integrations specifiques aux plugins de formulaire
* Plugin independant de tout builder — endpoint AJAX et PHP direct uniquement
* Simplification classe Public : uniquement output_public_nonce() et ajax_submit()
* Simplification orchestrateur : uniquement les hooks AJAX dans define_public_hooks()

= 1.1.0 =
* Endpoint AJAX universel (wp_ajax + wp_ajax_nopriv)
* Nonce public injecte automatiquement en wp_footer
* Classe WS_Brevo_FC_Sync statique — point d'entree unique
* Hook post-sync ws_brevo_fc_after_sync

= 1.0.0 =
* Version initiale
