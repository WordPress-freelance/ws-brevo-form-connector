=== WS Brevo Form Connector ===
Contributors: webstrategy
Tags: brevo, sendinblue, contact form, email marketing, crm
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Synchronise les soumissions de n'importe quel formulaire WordPress avec vos contacts Brevo.

== Description ==

WS Brevo Form Connector intercepte les soumissions de formulaires WordPress — quel que soit le builder utilisé — et synchronise automatiquement les contacts dans votre base Brevo.

**Builders supportés nativement**

* Contact Form 7
* Gravity Forms
* WPForms
* Elementor Forms Pro
* Avada / Fusion Forms
* Ninja Forms
* Fluent Forms
* Formidable Forms

**Endpoint AJAX universel**

Pour tout autre formulaire ou implémentation JavaScript custom, un endpoint AJAX est disponible (utilisateurs connectés et non connectés). Le nonce est injecté automatiquement en footer.

**Fonctionnalités**

* Configuration en quelques clics : clé API Brevo, liste cible, mapping des champs
* Règles par formulaire : dirigez chaque formulaire vers une liste Brevo différente
* Désactivez la synchronisation sur des formulaires spécifiques
* Journal des synchronisations (50 dernières entrées) avec statut OK/Erreur
* Test de connexion API intégré
* Appel PHP direct via `WS_Brevo_FC_Sync::contact()` pour les développeurs

== Installation ==

1. Téléchargez et activez le plugin depuis l'administration WordPress.
2. Rendez-vous dans **Brevo Connector** dans le menu d'administration.
3. Saisissez votre clé API Brevo (disponible dans Brevo → Paramètres → Clés API & SMTP).
4. Renseignez l'ID de votre liste Brevo cible.
5. Configurez le mapping des champs selon les noms utilisés dans vos formulaires.
6. Testez la connexion depuis l'onglet Configuration.

== Frequently Asked Questions ==

= Comment trouver mon ID de liste Brevo ? =

Dans Brevo, allez dans Contacts → Listes, cliquez sur votre liste. L'ID est visible dans l'URL de la page.

= Comment trouver le Field Name d'un champ Avada ? =

Dans le builder Avada, chaque champ de formulaire possède un paramètre "Field Name" (différent du label affiché). C'est cette valeur qu'il faut saisir dans le mapping.

= Mon formulaire n'est pas dans la liste des builders supportés. Que faire ? =

Utilisez l'endpoint AJAX universel documenté dans l'onglet "Endpoint AJAX" de la page de configuration. Il accepte les soumissions de n'importe quelle source.

= Comment désactiver la sync sur un formulaire précis ? =

Dans l'onglet "Règles par formulaire", ajoutez une règle avec l'ID du formulaire (visible dans le journal après une première soumission) et désactivez le toggle.

= Peut-on appeler la sync depuis du code PHP custom ? =

Oui : `WS_Brevo_FC_Sync::contact( 'email@example.com', ['PRENOM' => 'Jean'], 3, 'mon-hook' );`

== Changelog ==

= 1.1.0 =
* Ajout endpoint AJAX universel (wp_ajax + wp_ajax_nopriv)
* Nonce public injecté automatiquement en wp_footer
* Ajout adaptateurs : Ninja Forms, Fluent Forms, Formidable Forms
* Classe WS_Brevo_FC_Sync statique — point d'entrée unique appelable depuis du code custom
* Guard anti-double-fire pour Avada (avada_form_submit + fusion_form_submit)
* Hook post-sync ws_brevo_fc_after_sync pour extensions tierces

= 1.0.0 =
* Version initiale
* Support CF7, Gravity Forms, WPForms, Elementor Pro, Avada / Fusion Forms
* Page d'administration avec charte WebStrategy
* Mapping des champs configurable
* Règles par formulaire avec liste Brevo spécifique
* Journal des synchronisations (50 entrées)
* Test de connexion API intégré
