<?php

/**
 * Viene istanziato dalla classe BaseMBH
 * 
 * @base BaseMBH.php
 */

class AdminNotes {
	/**
	 * Variabile dove verrà salvato il nome della stringa transitoria.
	 *
	 * @dalla v0.14
	 *
	 * @accesso privato
	 * @var     string
	 */
	private $transientName = 'mbh_error_notices';
	
	/**
	 * Costruttore.
	 *
	 * @dalla v0.14
	 *
	 * @accesso pubblico
	 */
	public function __construct() {
		add_action('admin_notices', array($this, 'notices'));
	}
	
	/**
	 * Stampa i messaggi di errore a video.
	 *
	 * @dalla v0.14
	 *
	 * @accesso pubblico
	 */
	public function notices() {
		// Se non ci sono errori, esco dalla funzione
		if (!($listErrors = get_transient($this->transientName)))
			return;
		
		foreach ($listErrors as $error) {
			?>
			<div class="mbh-alert notice notice-<?= $error['type'] ?> is-dismissible">
				<p><?= $error['message'] ?></p>
			</div>
			<?php
		}
		
		delete_transient($this->transientName);
		remove_action('admin_notices', array($this, 'notices'));
	}
	
	/**
	 * Imposta i messaggi di errore da visualizzare in caso di mancata compilazione dei campi obbligatori.
	 *
	 * @dalla v0.14
	 *
	 * @accesso   pubblico
	 * @parametro array  $fieldRequired Obbligatorio. Lista dei campi obbligatori.
	 * @parametro string $msgError      Obbligatorio. Messaggio di errore da visualizzare.
	 */
	public function setErrors($fieldRequired, $msgError) {
		$getSettingsErrors = get_settings_errors();
		
		if (is_array($fieldRequired) && count($fieldRequired)) {
			foreach ($fieldRequired as $data) {
				$getSettings = array_column($getSettingsErrors, 'setting');
				
				if (!in_array('missing-error-'. $data['name'], $getSettings)) {
					add_settings_error(
						'missing-error-'. $data['name'],
						'missing-error-'. $data['name'],
						sprintf($msgError, $data['label']),
						'error'
					);
				}
			}
			
			delete_transient($this->transientName);
			set_transient($this->transientName, get_settings_errors(), 30);
		}
	}
}