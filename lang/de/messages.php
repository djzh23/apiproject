<?php

return [
    'user' => [
        'email' => [
            'exists' => 'Diese E-Mail-Adresse ist bereits registriert.',
        ],
        'registered' => 'Benutzer wurde erfolgreich registriert. Warten auf Freigabe.',
        'not_found' => 'Benutzer wurde nicht gefunden.',
        'found' => 'Benutzer wurde gefunden.',
        'not_approved' => 'Benutzer noch nicht freigegeben!',
        'no_role' => 'Keine Benutzerrolle zugewiesen. Bitte warten Sie auf die Freigabe.',
        'updated' => 'Benutzerinformationen erfolgreich aktualisiert.',
        'no_changes' => 'Keine Änderungen vorgenommen.',
        'update_error' => 'Fehler beim Aktualisieren der Benutzerinformationen.',
    ],
    'superadmin' => [
        'no_permission' => 'Keine Berechtigung.',
        'approved' => 'Benutzer wurde erfolgreich aktiviert.',
        'failure_approve' => 'Benutzer konnte nicht aktiviert werden.',
        'disapproved' => 'Benutzer wurde erfolgreich deaktiviert.',
        'failure_disapprove' => 'Benutzer konnte nicht deaktiviert werden.',
        'users_found' => 'Benutzer gefunden.',
        'no_users_found' => 'Keine Benutzer gefunden.',
    ],
    'auth' => [
        'invalid_password' => 'Ungültiges Passwort.',
        'invalid_credentials' => 'Ungültige Anmeldedaten.',
        'logout_success' => 'Erfolgreich abgemeldet.',
        'unauthorized' => 'Nicht autorisiert.',
        'superadmin_required' => 'Superadmin-Rechte erforderlich.',
    ],
    'validation' => [
        'password' => [
            'min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'server_error' => 'Etwas ist auf der Serverseite schiefgelaufen. Bitte versuchen Sie es später erneut!'
        ],
    ],
    'success' => [
        'saved' => 'Daten erfolgreich gespeichert.',
    ],
    'password' => [
        'reset_link_sent' => 'Link zum Zurücksetzen des Passworts wurde per E-Mail gesendet',
        'reset_success' => 'Passwort wurde erfolgreich zurückgesetzt',
    ],
    'errors' => [
        'invalid_email' => 'Diese E-Mail-Adresse ist ungültig oder nicht registriert.',
        'registration_failed' => 'Registrierung konnte nicht abgeschlossen werden.',
        'general' => 'Ein Fehler ist aufgetreten.',
        'server' => 'Serverfehler aufgetreten.',
        'invalid_request' => 'Ungültige Anfrage.',
        // weitere Fehlermeldungen...
    ],
];
