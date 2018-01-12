<?php

/**
 * Richiede la classe: MetaBoxesHandler
 * 
 * @base MetaBoxesHandler.class.php
 */

require_once('AdminNotes.php');

abstract class BaseMBH {
	/**
	 * Metodo astratto da sviluppare.
	 *
	 * @dalla v0.1
	 *
	 * @accesso   pubblico
	 */
	abstract public function returnAllMeta($includeAllPosts);
	
	/**
	 * Variabile dove verrà salvata la lingua utilizzata dal pannello Admin di Wordpress.
	 *
	 * @dalla v0.14
	 *
	 * @accesso privato
	 * @var     string
	 */
	private $lang = '';
	
	/**
	 * Tipi di lingua validi per la traduzione.
	 *
	 * @dalla v0.14
	 *
	 * @accesso privato
	 * @var     array
	 */
	private $validLanguage = array(
		'it_IT' => 'it',
		'en_US' => 'en',
	);
	
	/**
	 * Lista delle traduzioni.
	 *
	 * @dalla v0.14
	 *
	 * @accesso privato
	 * @var     array
	 */
	private $listTranslations = array();
	
	/**
	 * Variabile dove verrà salvata l'instanza della classe AdminNotes.
	 *
	 * @dalla v0.14
	 *
	 * @accesso privato
	 * @var     object
	 */
	private $adminNotes;
	
	/**
	 * Costante dove viene memorizzato il prefisso iniziale del campo usato nel metodo HTTP POST.
	 *
	 * @dalla v0.20
	 *
	 * @accesso pubblico
	 * @var     string
	 */
	const PREFIX_POST_FIELD = 'mbh_field_';
	
	/**
	 * Costruttore.
	 *
	 * @aggiornamento v0.22 Integrazione con le revisioni
	 * @dalla v0.1
	 *
	 * @accesso pubblico
	 */
	public function __construct($lang = false) {
		$this->_setLang($lang);
		
		$this->adminNotes = new adminNotes;
		
		if (is_admin()) {
			add_action('wp_print_scripts', function() {
				wp_deregister_script('autosave');
			});
			
			add_filter('_wp_post_revision_fields', array($this, 'wp_post_revision_fields'));
			add_action('wp_restore_post_revision', array($this, 'wp_restore_post_revision'), 10, 2);
		}
	}
	
	/*
	 * Richiamato da 'add_filter'. Metodo che rimpiazza quello predefinito di wordpress.
	 *
	 * Determina quali campi di post devono essere salvati nelle revisioni.
	 *
	 * @dalla v0.22
	 *
	 * @accesso pubblico
	 */	
	public function wp_post_revision_fields($returnPost) {
		global $post, $pagenow;
		
		$allowed = false;
		
		if ($pagenow == 'admin-ajax.php' && isset($_POST['action']) && $_POST['action'] == 'get-revision-diffs')
			$post_type = get_post_type($_POST['post_id']);
		else
			$post_type = $post->post_type;
		
		if ($post_type != $this->postType)
			return $returnPost;
		
		$allMetaKey = $this->_processFieldSettings(function($settings, $args) {
			$postMetas = $settings['name'];
			
			if (!is_array($postMetas)) {
				$tmp = $postMetas;
				
				$postMetas = array();
				
				$postMetas[] = $tmp;
			}
			
			foreach ($postMetas as $namePostMeta) {
				$args[$namePostMeta] = $settings['label'];
				
				add_filter('_wp_post_revision_field_'. $namePostMeta, array($this, 'wp_post_revision_field'), 10, 3);
			}
			
			return $args;
		});
		
		$returnPost = array_merge($returnPost, $allMetaKey);
		
		return $returnPost;
	}
	
	/*
	 * Richiamato da 'add_filter'. Metodo che rimpiazza quello predefinito di wordpress.
	 *
	 * Questo filtro caricherà il valore per il campo specificato e lo restituisce per il rendering
	 *
	 * @dalla v0.22
	 *
	 * @accesso pubblico
	 */
	public function wp_post_revision_field($value, $fieldName, $postObj = null) {
		global $revision, $post;
		
		$meta = get_metadata('post', $postObj->ID, $this->postType, true);
		
		return (isset($meta[$fieldName]) ? $meta[$fieldName] : '');
	}
	
	/*
	 * Richiamato da 'add_filter'. Metodo che rimpiazza quello predefinito di wordpress.
	 *
	 * Ripristina un post alla revisione specificata.
	 *
	 * @dalla v0.22
	 *
	 * @accesso pubblico
	 */
	public function wp_restore_post_revision($post_id, $revision_id) {
		global $post;
		
		if ($post->post_type != $this->postType)
			return;
		
		$revision = get_post($revision_id);
		
		$metaLoad = get_metadata('post', $revision->ID, $this->postType, true);
		
		$args = $this->_processFieldSettings(function($settings, $args) use ($metaLoad) {
			$postMetas = $settings['name'];
			
			if (!is_array($postMetas)) {
				$tmp = $postMetas;
				
				$postMetas = array();
				
				$postMetas[] = $tmp;
			}
			
			foreach ($postMetas as $namePostMeta) {
				if (!isset($settings['save-unique']) || isset($settings['save-unique']) && !$settings['save-unique'])
					$args['metaSave'][$namePostMeta] = (isset($metaLoad[$namePostMeta]) ? $metaLoad[$namePostMeta] : '');
				else {
					$singleMetaLoad = get_metadata('post', $revision->ID, $this->postType .'_'. $namePostMeta .'_single', true);
					
					if (false !== $singleMetaLoad)
						update_post_meta($post_id, $this->postType .'_'. $namePostMeta .'_single', $singleMetaLoad);
					else
						delete_post_meta($post_id, $this->postType .'_'. $namePostMeta .'_single');
				}
			}
			
			return $args;
		});
		
		if (is_array($args['metaSave']) && count($args['metaSave']))
			update_post_meta($post_id, $this->postType, $args['metaSave']);
	}
	
	/**
	 * Richiama questo metodo quando viene aggiunta l'azione di salvataggio del post.
	 *
	 * @aggiornamento v0.22 Integrazione con le revisioni
	 * @dalla v0.18
	 *
	 * @accesso   pubblico
	 * @parametro int $post_id Post ID.
	 */
	public function mbhSaveActionWP($post_id) {
		global $wpdb, $post;
		
		if ($post->post_type != $this->postType || in_array($_GET['action'], array('trash', 'untrash')))
			return;
		
		if ($_GET['action'] == 'restore') {
			if ($parent_id = wp_is_post_revision($post_id)) {
				$parent = get_post($parent_id);
				
				if ($parent->post_type == $this->postType) {
					$meta = get_post_meta($parent->ID, $this->postType, true);
					$allMetaKey = $this->_getMetaKeyFromNamePostMeta();
					
					foreach ($allMetaKey as $k => $v) {
						if (!in_array($k, array_keys($meta)))
							$meta[$k] = get_post_meta($parent->ID, $v, true);
					}
					
					if (false !== $meta)
						add_metadata('post', $post_id, $this->postType, $meta);
					
					return;
				}
				
			}
			
			return;
		}
		
		$__POST = array_map('stripslashes_deep', $_POST);
		
		if ($fieldRequired = $this->_checkRequiredFieldsInPOST($__POST))
			$this->adminNotes->setErrors($fieldRequired, $this->_getTranslate('error_field_required'));
		else {
			$title = $__POST['post_title'];
			
			if (!$this->allowHTMLInPostTitle)
				$title = wp_strip_all_tags($title, true);
			
			$title = $this->_getTextTransform($this->typeTransformationForTextPostTitle, $title);
			
			$wpdb->update($wpdb->posts, array('post_title' => $title), array('ID' => $post->ID));
			
			$args = $this->_processFieldSettings(function($settings, $args) use($__POST, $post) {
				$postMetas = $settings['name'];
				
				if (!is_array($postMetas)) {
					$tmp = $postMetas;
					
					$postMetas = array();
					
					$postMetas[] = $tmp;
				}
				
				foreach ($postMetas as $namePostMeta) {
					$valueFieldFromPOST = $__POST[self::PREFIX_POST_FIELD . $namePostMeta];
					
					if (count($postMetas) == 1) {
						if ($settings['type'] == 'input' || $settings['type'] == 'textarea') {
							if (!isset($settings['allow-html']) || isset($settings['allow-html']) && !$settings['allow-html'])
								$valueFieldFromPOST = strip_tags($valueFieldFromPOST);
						}
						
						if ($settings['type'] == 'input' && isset($settings['text-transform']) && $settings['text-transform'])
							$valueFieldFromPOST = $this->_getTextTransform($settings['text-transform'], $valueFieldFromPOST);
					}
					
					if (!isset($settings['save-unique']) || isset($settings['save-unique']) && !$settings['save-unique'])
						$args['metaSave'][$namePostMeta] = $valueFieldFromPOST;
					else
						update_post_meta($post->ID, $this->postType .'_'. $namePostMeta .'_single', $valueFieldFromPOST);
				}
				
				return $args;
			});
			
			if (is_array($args['metaSave']) && count($args['metaSave']))
				update_post_meta($post->ID, $this->postType, $args['metaSave']);
		}
		
		if ($parent_id = wp_is_post_revision($post_id)) {
			$parent = get_post($parent_id);
			
			if ($parent->post_type == $this->postType) {
				$meta = get_post_meta($parent->ID, $this->postType, true);
				$allMetaKey = $this->_getMetaKeyFromNamePostMeta();
				
				foreach ($allMetaKey as $k => $v) {
					if (!in_array($k, array_keys($meta)))
						$meta[$k] = get_post_meta($parent->ID, $v, true);
				}
				
				if (false !== $meta)
					add_metadata('post', $post_id, $this->postType, $meta);
				
				return;
			}
			
		}
	}
	
	/*################################################################################*/
	/*## METODI PROTETTI                                                            ##*/
	/*################################################################################*/
	
	/**
	 * Aggiunge l'azione di salvataggio del post.
	 *
	 * @aggiornamento v0.18 E' stata riscritta per essere richiamata più volte
	 * @dalla v0.1
	 *
	 * @accesso protetto
	 */
	protected function _WPaddActionSave() {
		if (!is_admin())
			return;
		
		remove_action('save_post', array($this, 'mbhSaveActionWP'));
		add_action('save_post', array($this, 'mbhSaveActionWP'));
	}
	
	/**
	 * Ritorna un array con tutti i postmeta e i loro valori salvati precedentemente su DB.
	 *
	 * Se l'oggetto post è vuoto o non corrisponde al custom post type impostato dalla classe, ritornerà un array multidimensionale
	 * che come chiave avrà l'ID del post.
	 *
	 * @aggiornamento v0.19.1 Aggiunto parametro $includeAllPosts per forzare il ritorno con tutti i post
	 * @dalla v0.1
	 *
	 * @accesso   protetto
	 * @parametro bool $includeAllPosts Facoltativo. Forza il ritorna con tutti i post.
	 * @ritorno   array
	 */
	protected function _getPostMetaAsArrays($includeAllPosts = false) {
		global $post;
		
		$this->_generalCheck();
		
		$getAllPostMeta = function($settings, $args, $metaFromDB) {
			return $this->_processFieldSettings(function($settings, $args) use($metaFromDB) {
				$postMetas = $settings['name'];
				
				if (!is_array($postMetas)) {
					$tmp = $postMetas;
					
					$postMetas = array();
					
					$postMetas[] = $tmp;
				}
				
				foreach ($postMetas as $namePostMeta) {
					$args['allPostMeta'][$namePostMeta] = $this->_getSingleMetaAsArray($settings, (isset($metaFromDB[$namePostMeta]) ? $metaFromDB[$namePostMeta] : ''));
				}
				
				return $args;
			});
		};
		
		if (!$post || get_post_type($post) != $this->postType || $includeAllPosts) {
			$args = array(
				'posts_per_page'   => -1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'post_type'        => $this->postType,
				'post_status'      => 'publish',
				'suppress_filters' => true,
			);
			
			$getPosts = get_posts($args);
			
			if (count($getPosts)) {
				$allPostMeta = array();
				
				foreach ($getPosts as $dataPost) {
					$metaFromDB = $this->_getPostMetaFromDB($dataPost);
					
					$args = $getAllPostMeta($settings, $args, $metaFromDB);
					
					$allPostMeta[$dataPost->ID] = $args['allPostMeta'];
				}
				
				return $allPostMeta;
			}
		} else {
			$metaFromDB = $this->_getPostMetaFromDB($post);
			
			$args = $getAllPostMeta($settings, $args, $metaFromDB);
			
			return $args['allPostMeta'];
		}
	}
	
	/**
	 * Alcuni controlli generali per il funzionamento corretto della classe.
	 *
	 * @dalla v0.1
	 *
	 * @accesso protetto
	 */
	protected function _generalCheck() {
		if (!$this->postType) {
			wp_die('
				Non è stato specificato il nome del "Post Type" nella classe <strong>'. get_class($this) .'</strong>!<br>
				<br>
				Richiamare il metodo <strong>$mbh->setPostType(\'&lt;nome del custom post type&gt;\')</strong>
			');
		}
		
		if (!count($this->fields)) {
			wp_die("
				È necessario aggiungere almeno un \"campo\" nella classe <strong>". get_class($this) ."</strong>!<br>
				<br>
				Richiamare il metodo <strong>". '$' ."mbh->add('&lt;variabile array&gt;')</strong><br>
				<br>
				<em>Esempio:</em><br>
<pre>
". '$' ."mbh->add(array(
	array(
		'name' => 'nome',
		'label' => 'Nome',
		'type' => 'input',
		'type-attributes' => array('style' => 'color: #000', 'class' => 'text'),
		'help-description' => '<strong>Facoltativo.</strong> Inserire il nome originale.',
		'text-transform' => 'uppercase',
	),
	array(
		'name' => 'cognome',
		'label' => 'Cognome',
		'type' => 'input',
	),
	array(
		'name' => 'indirizzo',
		'label' => 'Indirizzo',
		'type' => 'textarea',
		'type-attributes' => array('cols' => 10, 'rows' => 10),
		'help-description' => 'Inserire ogni voce separata da un ritorno a capo.',
		'save-unique' => true,
		'allow-html' => true,
	),
));
</pre>
			");
		}
	}
	
	/**
	 * Ottiene la lingua base impostata in precedenza.
	 *
	 * @dalla v0.14
	 *
	 * @accesso protetto
	 */
	protected function _getLang() {
		return $this->lang;
	}
	
	/**
	 * Ritorna la frase tradotta dalla stringa ID di traduzione e in base alla lingua impostata
	 *
	 * @dalla v0.14
	 *
	 * @accesso   protetto
	 * @parametro string $stringID Obbligatorio. Stringa ID della traduzione.
	 * @ritorno   string Ritorna con la frase tradotta come stringa.
	 */
	protected function _getTranslate($stringID) {
		return $this->listTranslations[$stringID][$this->lang];
	}
	
	/**
	 * Solo lato backend: Aggiunge il Meta Box di Wordpress per il supporto all'upload delle immagini.
	 *
	 * @dalla v0.15 E' diventato un metodo protetto e richiamabile automaticamente dalla classe
	 * @dalla v0.12
	 *
	 * @accesso protetto
	 */
	protected function _addMetaBoxWPImageUpload() {
		global $post;
		
		if (!is_admin())
			return false;
		
		$this->_generalCheck();
		
		$metaFromDB = $this->_getPostMetaFromDB($post);
		
		foreach ($this->fields as $settings) {
			if ($settings['type'] == 'wp-upload-image') {
				$imageTagID = $this->postType .'_'. $settings['name'];
				
				if (wp_script_is('wp-upload-image', 'registered') && !wp_script_is('wp-upload-image', 'enqueued'))
					wp_enqueue_script('wp-upload-image');
				
				add_meta_box('mb_'. $imageTagID, $settings['label'], function($post) use($imageTagID, $metaFromDB, $settings) {
					if ($metaFromDB[$settings['name']])
						$image = wp_get_attachment_image($metaFromDB[$settings['name']], (isset($settings['image-support-size']) && $settings['image-support-size'] ? $settings['image-support-size'] : 'medium'), false, array('style' => 'width: 100%; height: auto; cursor: pointer;', 'data-action' => 'add-media', 'data-target' => '#h_'. $imageTagID, 'data-preview' => '#'. $imageTagID));
					else
						$image = '';
					
					echo '
					<div id="'. $imageTagID .'" data-target="#h_'. $imageTagID .'" data-preview="#'. $imageTagID .'">'. $image .'</div>
					<p class="hide-if-no-js">
						<a class="button" data-action="add-media" data-target="#h_'. $imageTagID .'" data-preview="#'. $imageTagID .'">'. ($image ? $this->_getTranslate('change_image') : $this->_getTranslate('set_image')) .'</a>
						<a class="button" data-action="detach-media" data-target="#h_'. $imageTagID .'" data-preview="#'. $imageTagID .'"'. ($image ? '' : ' style="display: none;"') .'>'. $this->_getTranslate('remove_image') .'</a>
					</p>
					'. (isset($settings['help-description']) && $settings['help-description'] ? '<p>'. $settings['help-description'] .'</p>' : '') .'
					<input type="hidden" value="'. ($image ? $metaFromDB[$settings['name']] : '').'" id="h_'. $imageTagID .'" name="'. self::PREFIX_POST_FIELD . $settings['name'] .'">
					';
				}, $this->postType, 'side');
			}
		}
	}
	
	/**
	 * Aggiunge il Meta Box di Wordpress.
	 *
	 * @dalla v0.18
	 *
	 * @accesso protetto
	 * @parametro string $metaBoxName Obbligatorio. Nome del metabox.
	 * @parametro array  $fields      Obbligatorio. Lista con i valori di settaggio dei campi.
	 * @ritorno   string Ritorna con la frase tradotta come stringa.
	 */
	protected function _addMetaBox($metaBoxName, $fields) {
		if (!is_admin())
			return false;
		
		$this->_generalCheck();
		
		$randName = uniqid('', true);
		
		add_meta_box($this->postType .'_'. $randName .'_metabox', $metaBoxName, function () use($randName, $fields) {
			wp_nonce_field($this->postType .'_'. $randName .'_meta_nonce', $this->postType .'_'. $randName .'_meta_nonce');
			
			echo $this->_renderTableForAdmin($fields);
		}, $this->postType, 'normal', 'high');
	}
	
	/**
	 * Ritorna il nome esatto del meta key salvato sul DB
	 *
	 * @dalla v0.21
	 *
	 * @accesso   protetto
	 * @parametro string $namePostMeta Obbligatorio. Il nome del campo del Post Meta impostato. Se non viene passato nessun valore, ritorneranno tutti i metakey.
	 * @ritorno   string|array
	 */
	protected function _getMetaKeyFromNamePostMeta($namePostMeta = false) {
		$args = $this->_processFieldSettings(function($settings, $args) use ($namePostMeta) {
			$namePostMetaFromSettings = $settings['name'];
			
			if (!is_array($namePostMetaFromSettings)) {
				$tmp = $namePostMetaFromSettings;
				
				$namePostMetaFromSettings = array();
				
				$namePostMetaFromSettings[] = $tmp;
			}
			
			foreach ($namePostMetaFromSettings as $nPMeta) {
				if (isset($settings['save-unique']) && $settings['save-unique']) {
					if ($namePostMeta && $nPMeta == $namePostMeta)
						$args['single'] = $this->postType .'_'. $nPMeta .'_single';
					
					if (!$namePostMeta)
						$args['all'][$nPMeta] = $this->postType .'_'. $nPMeta .'_single';
				} else {
					if ($namePostMeta && $nPMeta == $namePostMeta)
						$args['single'] = $nPMeta;
					
					if (!$namePostMeta)
						$args['all'][$nPMeta] = $nPMeta;
				}
			}
			
			return $args;
		});
		
		if (isset($args['single']))
			return $args['single'];
		else
			return $args['all'];
	}
	
	
	/*################################################################################*/
	/*## METODI PRIVATI                                                             ##*/
	/*################################################################################*/
	
	/**
	 * Processa tutti i settaggi dei postmeta e chiama una funzione anonima per eseguire delle operazioni personalizzate
	 *
	 * @dalla v0.1
	 *
	 * @accesso   privato
	 * @parametro function $closure Obbligatorio. Funzione anonima da eseguire.
	 * @parametro array    $fields  Facoltativo. Una lista di parametri da parsare.
	 * @parametro array    $args    Facoltativo. Un array associativo con la lista delle variabili da memorizzare.
	 * @ritorno   array
	 */
	private function _processFieldSettings($closure, $fields = false, $args = false) {
		if (!is_callable($closure))
			wp_die('Nel metodo <strong>'. get_class($this) .'</strong> il primo parametro deve essere una funzione anonima!');
		
		if (!$fields)
			$fields = $this->fields;
		
		foreach ($fields as $settings) {
			if (isset($settings['more-elements']) && is_array($settings['more-elements']) && count($settings['more-elements'])) {
				$args = $this->_processFieldSettings($closure, $settings['more-elements'], $args);
			} else {
				if (isset($settings['name']) && $settings['name']) {
					$args = $closure($settings, $args);
				}
			}
		}
		
		return $args;
	}
	
	/**
	 * Carica tutto il contenuto dei campi meta dal DB.
	 *
	 * @aggiornamento v0.13
	 * @dalla v0.1
	 *
	 * @accesso   privato
	 * @parametro object $post Obbligatorio. L'oggetto del post.
	 * @ritorno   array
	 */
	private function _getPostMetaFromDB($post) {
		$loadMeta = get_post_meta($post->ID, $this->postType, true);
		
		$meta = array();
		
		$args = $this->_processFieldSettings(function($settings, $args) use($loadMeta, $post) {
			$postMetas = $settings['name'];
			
			if (!is_array($postMetas)) {
				$tmp = $postMetas;
				
				$postMetas = array();
				
				$postMetas[] = $tmp;
			}
			
			foreach ($postMetas as $namePostMeta) {
				if (isset($settings['save-unique']) && $settings['save-unique'])
					$args['meta'][$namePostMeta] = get_post_meta($post->ID, $this->postType .'_'. $namePostMeta .'_single', true);
				elseif (isset($loadMeta[$namePostMeta]))
					$args['meta'][$namePostMeta] = $loadMeta[$namePostMeta];
				else
					$args['meta'][$namePostMeta] = '';
			}
			
			return $args;
		});
		
		return $args['meta'];
	}
	
	/**
	 * Costruisce la tabella con tutti i postmeta.
	 *
	 * @aggiornamento v0.18 Aggiunto parametro $fields e il metodo è diventato privato
	 * @dalla v0.1
	 *
	 * @accesso   privato
	 * @parametro array  $fields Obbligatorio. Lista con i valori di settaggio dei campi.
	 * @ritorno   string Codice HTML.
	 */
	private function _renderTableForAdmin($fields) {
		global $post;
		
		$this->_generalCheck();
		
		$this->_appendCodeOnPage();
		
		$html = '
		<table class="form-table">
		';
		
		$metaFromDB = $this->_getPostMetaFromDB($post);
		
		foreach ($fields as $settings) {
			$postMetas = $settings['name'];
			
			if (!is_array($postMetas) && $settings['type'] != 'custom-html' && $settings['type'] != 'checkbox')
				$contentPostMeta = (isset($metaFromDB[$postMetas]) ? $metaFromDB[$postMetas] : '');
			elseif (is_array($postMetas) && ($settings['type'] == 'custom-html' || $settings['type'] == 'checkbox')) {
				$contentPostMeta = array();
				
				foreach ($postMetas as $namePostMeta) {
					$contentPostMeta[$namePostMeta] = (isset($metaFromDB[$namePostMeta]) ? $metaFromDB[$namePostMeta] : '');
				}
			} else
				$contentPostMeta = '';
			
			if (isset($settings['more-elements']) && is_array($settings['more-elements']) && count($settings['more-elements']))
				$html .= $this->_generateSubTableAsHTML($settings, $metaFromDB);
			else
				$html .= $this->_generateSingleRowAsHTML($settings, $contentPostMeta);
		}
		
		$html .= '
		</table>
		';
		
		return $html;
	}
	
	/**
	 * Imposta la lingua base in base a quella settata da Wordpress.
	 *
	 * @dalla v0.14
	 *
	 * @accesso privato
	 */
	private function _setLang($lang = false) {
		$getLang = ($lang ? $lang : get_locale());
		
		if (in_array($getLang, array_keys($this->validLanguage)))
			$this->lang = $this->validLanguage[$getLang];
		elseif (in_array($getLang, $this->validLanguage))
			$this->lang = $getLang;
		else
			$this->lang = array_values($this->validLanguage)[0];
		
		$this->loadTranslations();
	}
	
	/**
	 * Esplode tutti gli attributi HTML, separando ogniuno con chiave=valore.
	 *
	 * @dalla v0.1
	 *
	 * @accesso   privato
	 * @parametro array $attrs Obbligatorio. Lista degli attributi HTML.
	 * @ritorno   array
	 */
	private function _explodeAttributes($attrs) {
		$out = array();
		
		if (isset($attrs) && is_array($attrs)) {
			foreach ($attrs as $k => $v) {
				if ($k && !is_array($v))
					$out[] = $k .'="'. $v .'"';
				elseif ($k && is_array($v))
					$out[] = $k .'="'. implode(' ', $v) .'"';
			}
		}
		
		return $out;
	}
	
	/**
	 * Aggiunge eventuali codici Javascript/HTML inline, nella pagina backend.
	 *
	 * @aggiornamento v0.13 Aggiunto parametro $fields
	 * @dalla v0.1
	 *
	 * @accesso   privato
	 * @parametro array $fields Facoltativo. Una lista di parametri da parsare.
	 */
	private function _appendCodeOnPage($fields = false) {
		foreach ((!$fields ? $this->fields : $fields) as $settings) {
			if (isset($settings['more-elements']) && is_array($settings['more-elements']) && count($settings['more-elements'])) {
				$this->_appendCodeOnPage($settings['more-elements']);
			} elseif (isset($settings['type']) && $settings['type']) {
				switch ($settings['type']) {
					case 'media-upload':
						if (wp_script_is('custom-attachment', 'registered') && !wp_script_is('custom-attachment', 'enqueued'))
							wp_enqueue_script('custom-attachment');
						
						break;
				}
			}
		}
	}
	
	/**
	 * Ottiene la trasformazione del testo.
	 *
	 * @dalla v0.1
	 *
	 * @accesso   privato
	 * @parametro string $type Obbligatorio. La tipologia di trasformazione. Un valore tra (none|capitalize|uppercase|lowercase).
	 * @parametro string $text Obbligatorio. Il testo da trasformare.
	 * @ritorno   string Testo formattato.
	 */
	private function _getTextTransform($type, $text) {
		if (!in_array($type, $this->validTextTransform))
			$type = $this->validTextTransform[0];
		
		switch ($type) {
			case 'capitalize':
				$text = ucwords(strtolower($text));
				break;
			case 'uppercase':
				$text = strtoupper($text);
				break;
			case 'lowercase':
				$text = strtolower($text);
				break;
			case 'onlyfirst':
				$text = ucfirst(strtolower($text));
				break;
		}
		
		return $text;
	}
	
	/**
	 * Genera una riga singola di tabella con il postmeta e il suo valore salvato precedentemente.
	 *
	 * @aggiornamento v0.20 Aggiunto il campo radio
	 * @dalla v0.1
	 *
	 * @accesso   privato
	 * @parametro array         $settings        Obbligatorio. Lista parametri per il singolo postmeta.
	 * @parametro string|array  $contentPostMeta Obbligatorio. Il valore salvato del singolo postmeta.
	 * @ritorno   string Ritorna la singola riga.
	 */
	private function _generateSingleRowAsHTML($settings, $contentPostMeta) {
		$postMetas = $settings['name'];
		$attrs = array();
		$requiredField = false;
		
		if (isset($settings['type-attributes']) && is_array($settings['type-attributes']) && count($settings['type-attributes']))
			$attrs = $this->_explodeAttributes($settings['type-attributes']);
		
		if (isset($settings['required']) && $settings['required'])
			$requiredField = ' (*)';
		
		if (!is_array($postMetas))
			$namePostMeta = $postMetas;
		
		switch ($settings['type']) {
			case 'input':
				return '
				<tr>
					<th>
						<label for="'. self::PREFIX_POST_FIELD . $namePostMeta .'">'. $settings['label'] . ($requiredField ? $requiredField : '') .'</label>
					</th>
					<td>
						<input type="text" name="'. self::PREFIX_POST_FIELD . $namePostMeta .'" id="'. self::PREFIX_POST_FIELD . $namePostMeta .'" autocomplete="off" value="'. esc_attr((isset($contentPostMeta) ? $contentPostMeta : '')) .'" '. implode(' ', $attrs) .'>
						'. (isset($settings['help-description']) && $settings['help-description'] ? '<p class="description">'. $settings['help-description'] .'</p>' : '') .'
					</td>
				</tr>
				';
				
				break;
			case 'textarea':
				return '
				<tr>
					<th>
						<label for="'. self::PREFIX_POST_FIELD . $namePostMeta .'">'. $settings['label'] . ($requiredField ? $requiredField : '') .'</label>
					</th>
					<td>
						<textarea name="'. self::PREFIX_POST_FIELD . $namePostMeta .'" id="'. self::PREFIX_POST_FIELD . $namePostMeta .'" '. implode(' ', $attrs) .'>'. (isset($contentPostMeta) ? $contentPostMeta : '') .'</textarea>
						'. (isset($settings['help-description']) && $settings['help-description'] ? '<p class="description">'. $settings['help-description'] .'</p>' : '') .'
					</td>
				</tr>
				';
				
				break;
			case 'media-upload':
				return '
				<tr>
					<th>
						<button type="button" class="custom-attachment button hide-if-no-js">'. $settings['label'] . ($requiredField ? $requiredField : '') .'</button>
						<noscript>
							'. $settings['label'] .'
						</noscript>
					</th>
					<td>
						<input type="text" name="'. self::PREFIX_POST_FIELD . $namePostMeta .'" class="large-text" autocomplete="off" value="'. (isset($contentPostMeta) ? $contentPostMeta : '') .'">
						'. (isset($settings['help-description']) && $settings['help-description'] ? '<p class="description">'. $settings['help-description'] .'</p>' : '') .'
					</td>
				</tr>
				';
				
				break;
			case 'select':
				if (!isset($settings['select-options']))
					break;
				
				return '
				<tr>
					<th>
						<label for="'. self::PREFIX_POST_FIELD . $namePostMeta .'">'. $settings['label'] . ($requiredField ? $requiredField : '') .'</label>
					</th>
					<td>
						<select name="'. self::PREFIX_POST_FIELD . $namePostMeta .'" id="'. self::PREFIX_POST_FIELD . $namePostMeta .'">
							'. $this->_generateSelectOptionAsHTML($settings['select-options'], (isset($contentPostMeta) ? $contentPostMeta : '')) .'
						</select>
						'. (isset($settings['help-description']) && $settings['help-description'] ? '<p class="description">'. $settings['help-description'] .'</p>' : '') .'
					</td>
				</tr>
				';
				
				break;
			case 'custom-html':
				if (isset($settings['custom-html']) && is_callable($settings['custom-html'])) {
					$closure = $settings['custom-html'];
					
					$fields = array();
					
					foreach ($postMetas as $nameMeta) {
						$fields[$nameMeta] = self::PREFIX_POST_FIELD . $nameMeta;
					}
					
					return '
					<tr>
						<th>
							<label>'. $settings['label'] . ($requiredField ? $requiredField : '') .'</label>
						</th>
						<td>
							'. $closure($fields, $contentPostMeta) .'
						</td>
					</tr>
					';
				}
				
				break;
			case 'checkbox':
				if (!isset($settings['list-checkbox']))
					break;
				
				return '
				<tr>
					<th>
						<label>'. $settings['label'] .'</label>
					</th>
					<td>
						<fieldset>
							'. $this->_generateListCheckboxAsHTML($settings['list-checkbox'], (isset($contentPostMeta) ? $contentPostMeta : '')) .'
							'. (isset($settings['help-description']) && $settings['help-description'] ? '<p class="description">'. $settings['help-description'] .'</p>' : '') .'
						</fieldset>
					</td>
				</tr>
				';
				
				break;
			case 'radio':
				if (!isset($settings['list-radio']))
					break;
				
				return '
				<tr>
					<th>
						<label>'. $settings['label'] . ($requiredField ? $requiredField : '') .'</label>
					</th>
					<td>
						<fieldset>
							'. $this->_generateListRadioAsHTML($namePostMeta, $settings['list-radio'], (isset($contentPostMeta) ? $contentPostMeta : '')) .'
							'. (isset($settings['help-description']) && $settings['help-description'] ? '<p class="description">'. $settings['help-description'] .'</p>' : '') .'
						</fieldset>
					</td>
				</tr>
				';
				
				break;
		}
	}
	
	/**
	 * Genera una select box con le relative opzioni e ritorna il contenuto HTML.
	 *
	 * @dalla v0.13
	 *
	 * @accesso   privato
	 * @parametro array  $array   Obbligatorio. Lista delle opzioni. Supporto anche per i gruppi.
	 * @parametro string $default Facoltativo. Il valore predefinito da selezionare nella options.
	 * @ritorno   string Ritorna il contenuto codice HTML.
	 */
	private function _generateSelectOptionAsHTML($array, $default = false) {
		if (is_array($array) && count($array)) {
			$options = '';
			
			foreach ($array as $key => $value) {
				if (is_array($value)) {
					$group = $value;
					
					$options .= '
					<optgroup label="'. $key .'">
					';
					
					foreach ($group as $key => $value) {
						$options .= '
						<option value="'. $value .'"'. ($default == $value ? ' selected' : '') .'>'. $key .'</option>
						';
					}
					
					$options .= '
					</optgroup>
					';
				} else {
					$options .= '
					<option value="'. $value .'"'. ($default == $value ? ' selected' : '') .'>'. $key .'</option>
					';
				}
			}
			
			return $options;
		}
	}
	
	/**
	 * Genera una lista di checkbox e ritorna il contenuto HTML.
	 *
	 * @dalla v0.19
	 *
	 * @accesso   privato
	 * @parametro array  $array    Obbligatorio. Lista delle opzioni.
	 * @parametro array  $defaults Facoltativo. Il valore predefinito da selezionare nella checkbox.
	 * @ritorno   string Ritorna il contenuto codice HTML.
	 */
	private function _generateListCheckboxAsHTML($array, $defaults = array()) {
		if (is_array($array) && count($array)) {
			$output = '';
			
			foreach ($array as $key => $data) {
				$default = array_search($key, array_keys($defaults));
				
				if ($default !== false && $defaults[$key] == current($data))
					$checked = ' checked';
				else
					$checked = '';
				
				$output .= '
				<label>
					<input name="'. self::PREFIX_POST_FIELD . $key .'" value="'. current($data) .'" type="checkbox"'. $checked .'>
					'. key($data) .'
				</label>
				<br>
				';
			}
			
			return $output;
		}
	}
	
	/**
	 * Genera una lista di radio e ritorna il contenuto HTML.
	 *
	 * @dalla v0.20
	 *
	 * @accesso   privato
	 * @parametro string $namePostMeta Obbligatorio. Nome del postmeta.
	 * @parametro array  $array        Obbligatorio. Lista delle opzioni.
	 * @parametro string $default      Facoltativo. Il valore predefinito da selezionare nella radio.
	 * @ritorno   string Ritorna il contenuto codice HTML.
	 */
	private function _generateListRadioAsHTML($namePostMeta, $array, $default = '') {
		if (is_array($array) && count($array)) {
			$output = '';
			
			foreach ($array as $data) {
				$output .= '
				<label>
					<input name="'. self::PREFIX_POST_FIELD . $namePostMeta .'" value="'. current($data) .'" type="radio"'. ($default == current($data) ? ' checked' : '') .'>
					'. key($data) .'
				</label>
				<br>
				';
			}
			
			return $output;
		}
	}
	
	/**
	 * Genera una riga con all'interno una sotto-tabella per gestire i singoli postmeta.
	 *
	 * @dalla v0.13
	 *
	 * @accesso   privato
	 * @parametro array  $groupSettings Obbligatorio. Lista parametri di una serie di postmeta.
	 * @parametro array  $allPostMeta   Obbligatorio. Il valore salvato di tutti i postmeta.
	 * @ritorno   string Ritorna la singola riga.
	 */
	private function _generateSubTableAsHTML($groupSettings, $allPostMeta) {
		if (isset($groupSettings['more-elements']) && is_array($groupSettings['more-elements']) && count($groupSettings['more-elements'])) {
			$html = '
			<tr>
				<th><label>'. $groupSettings['label'] .'</label></th>
				<td>
					<table class="form-sub-table">
			';
			
			foreach ($groupSettings['more-elements'] as $settings) {
				$namePostMeta = $settings['name'];
				
				$html .= $this->_generateSingleRowAsHTML($settings, (isset($allPostMeta[$namePostMeta]) ? $allPostMeta[$namePostMeta] : ''));
			}
			
			$html .= '
					</table>
				</td>
			</tr>
			';
			
			return $html;
		}
	}
	
	/**
	 * Ritorna un array singolo postmeta.
	 *
	 * @dalla v0.1
	 *
	 * @accesso   privato
	 * @parametro array $settings        Obbligatorio. Lista parametri per il singolo postmeta.
	 * @parametro array $contentPostMeta Obbligatorio. Il valore salvato del singolo postmeta.
	 * @ritorno   array 
	 */
	private function _getSingleMetaAsArray($settings, $contentPostMeta) {
		return array(
			'label' => $settings['label'],
			'content' => $contentPostMeta,
		);
	}
	
	/**
	 * Ottiene un array di campi obbligatori.
	 *
	 * @aggiornamento v0.21
	 * @dalla v0.14
	 *
	 * @accesso   privato
	 * @ritorno   array Ritornano i campi che sono obbligatori.
	 */
	private function _getRequiredFields() {
		$args = $this->_processFieldSettings(function($settings, $args) {
			if (isset($settings['required']) && $settings['required']) {
				$args['requiredFields'][] = array(
					'name' => $settings['name'],
					'label' => $settings['label'],
					'closure' => (is_callable($settings['required']) ? $settings['required'] : false),
				);
			}
			
			return $args;
		});
		
		return $args['requiredFields'];
	}
	
	/**
	 * Controlla che i campi obbligatori siano stati compilati correttamente nel $_POST.
	 *
	 * @aggiornamento v0.21
	 * @dalla v0.14
	 *
	 * @accesso   privato
	 * @parametro array       $__POSTS       Obbligatorio. Tutti i postmeta salvati.
	 * @ritorno   false|array Ritorna false se tutti i campi obbligatori sono stati correttamente compilati o non vi sono campi obbligatori;
	 *                        Ritorna un array con la lista dei campi obbligatori da compilare.
	 */
	private function _checkRequiredFieldsInPOST($__POSTS) {
		$fieldRequired = $this->_getRequiredFields();
		
		if (is_array($fieldRequired) && count($fieldRequired)) {
			$cloneFieldRequired = $fieldRequired;
			
			foreach ($fieldRequired as $k => $data) {
				$namePostMeta = $data['name'];
				$closure = $data['closure'];
				
				if (!$closure) {
					if (isset($__POSTS[self::PREFIX_POST_FIELD . $namePostMeta]) && $__POSTS[self::PREFIX_POST_FIELD . $namePostMeta])
						unset($cloneFieldRequired[$k]);
				} else {
					$returnClosure = $closure($__POSTS[self::PREFIX_POST_FIELD . $namePostMeta]);
					
					if (!$returnClosure)
						unset($cloneFieldRequired[$k]);
					else
						$cloneFieldRequired[$k]['message'] = $returnClosure;
				}
			}
			
			if (is_array($cloneFieldRequired) && count($cloneFieldRequired))
				return $cloneFieldRequired;
			else
				return false;
		} else
			return false;
	}
	
	/**
	 * Carica tutte le traduzioni da un file e le imposta in una variabile locale.
	 *
	 * @dalla v0.14
	 *
	 * @accesso privato
	 */
	private function loadTranslations() {
		include 'translations.php';
		
		$this->listTranslations = $translations;
	}
}