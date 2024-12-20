<?php

return [
    'server_error' => 'Etwas ist auf der Serverseite schiefgelaufen. Bitte versuchen Sie es später erneut!',
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
        'register' => [
            'success' => 'Registrierung erfolgreich.',
            'failed' => 'Registrierung fehlgeschlagen.',
        ],
        'login' => [
            'success' => 'Anmeldung erfolgreich.',
            'failed' => 'Anmeldung fehlgeschlagen.',
        ],
        'password' =>[
            'failed' => 'Ungültiges Passwort.',
        ],
        'invalid_password' => 'Ungültiges Passwort.',
        'invalid_credentials' => 'Ungültige Anmeldedaten.',
        'logout_success' => 'Erfolgreich abgemeldet.',
        'unauthorized' => 'Nicht autorisiert.',
        'superadmin_required' => 'Superadmin-Rechte erforderlich.',
        'admin_required' => 'Admin-Rechte erforderlich.',
    ],
    'validation' => [
        'email' => [
            'required' => 'Die E-Mail-Adresse ist erforderlich.',
            'max_length' => 'Die E-Mail-Adresse darf nicht länger als 255 Zeichen sein.',
            'invalid' => 'Die E-Mail-Adresse ist ungültig. Bitte stellen Sie sicher, dass Sie das "@"-Zeichen eingegeben haben.',
            'not_found' => 'E-Mail-Adresse nicht gefunden.',
        ],
        'password' => [
            'min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
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

    ],
    'billing' => [
        'preview' => [
            'success' => 'Preview war erfolgreich.',
            'failed' => 'Preview war nicht erfolgreich, bitte prüfen Sie nochmal die Eingaben.',
            'validation_failed' => 'Die Validierung hat fehlgeschlagen, bitte die Eingabe prüfen .',
            'invalid' => 'Ungültige Rechnungsnummer oder Rechnungsnummer ist bereits vergeben.'
        ],
        'fetch' => [
            'success' => 'Rechnung erfolgreich abgerufen.',
            'failed' => 'Rechnung konnte nicht abgerufen werden.',
            'not_found' => 'Keine Rechnung gefunden.',
        ],
        'create' => [
            'success' => 'Rechnung erfolgreich erstellt.',
            'failed' => 'Rechnung konnte nicht erstellt werden.',
        ],
        'pdf' => [
            'upload' => [
                'success' => 'PDF erfolgreich hochgeladen.',
                'failed' => 'PDF konnte nicht hochgeladen werden.',
                'failed_pdf' => 'PDF ist bereit hochgeladen.',
            ],
            'download' => [
                'success' => 'PDF erfolgreich heruntergeladen.',
                'failed' => 'PDF konnte nicht heruntergeladen werden.',
            ],
            'month_pagination' => [
                'success' => 'Rechnungen mit PDFs erfolgreich für diesen Monat paginiert.',
                'empty' => 'Keine Rechnungen für den ausgewählten Monat gefunden.',
                'failed' => 'Keine Rechnungen für den ausgewählten Monat konnten paginiert werden.',
            ],
            'list' => [
                'success' => 'PDF-Liste erfolgreich abgerufen.',
                'failed' => 'PDF-Liste konnte nicht abgerufen werden.',
            ],
            'not_found' => 'PDF wurde nicht gefunden.',
        ],
        'errors' => [
            'general' => 'Ein Fehler ist aufgetreten.',
            'unauthorized' => 'Nicht autorisierter Zugriff.',
        ],
        'count' => [
            'success' => 'Anzahl der Rechnungen erfolgreich abgerufen.',
            'failed' => 'Anzahl der Rechnungen konnte nicht abgerufen werden.',
        ],
    ],
    'work' =>[
        'create' => [
            'success' => 'Einsatzt erfolgreich erstellt.',
            'failed' => 'Einsatz konnte nicht erstellt werden.',
            'validation_failed' => 'Die Validierung hat fehlgeschlagen, bitte die Eingabe prüfen .',
            'unique_work_validation_failed' => 'Für dieses Datum ist bereits ein Einsatz mit demselben Team gespeichert. Bitte prüfen Sie die Eingaben.',
        ],
        'fetch' => [
            'success' => 'Einsätze wurden erfolgreich geladen.',
            'failed' => 'Einsätze konnten nicht geladen werden.',
            'validation_failed' => 'Die Validierung hat fehlgeschlagen, bitte die Eingabe prüfen .',
            'by_team' => [
                'success' => 'Einsätze wurden erfolgreich nach Team geladen.',
                'empty' => 'Keine Einsätze für dieses Team gefunden.',
                'failed' => 'Problem beim Laden von Einsätze nach Team .',
            ],
        ],
        'update' => [
            'success' => 'Einsatz erfolgreich aktualisiert.',
            'failed' => 'Einsatz konnte nicht aktualisiert werden.',
            'validation_failed' => 'Die Validierung hat fehlgeschlagen, bitte die Eingabe prüfen .',
            'not_found' => 'Einsatz wurde nicht gefunden.',
        ],
        'pdf' =>[
            'upload' => [
                'success' => 'PDF erfolgreich erstellt.',
                'failed' => 'PDF konnte nicht erstellt werden.',
            ],
            'download' => [
                'success' => 'PDF erfolgreich heruntergeladen.',
                'failed' => 'PDF konnte nicht heruntergeladen werden.',
            ],
            'not_found' => 'PDF - Einsatzt id wurde nicht gefunden.',
        ],
        'count' => [
            'standing' => [
                'success' => 'Anzahl der unkompletten Einsätze erfolgreich abgerufen.',
                'failed' => 'Anzahl der unkompletten Einsätze konnte nicht abgerufen werden.',
            ],
            'all' => [
                'success' => 'Anzahl der abgeschlossenen Einsätze erfolgreich abgerufen.',
                'failed' => 'Anzahl der abgeschlossenen Einsätze konnte nicht abgerufen werden.',
            ],

        ],
    ]
];
