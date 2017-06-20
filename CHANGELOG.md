Changelog
=========

#### v0.20 - 20 Giugno 2017
* Aggiuno nuovo campo input radio (è possibile inserire più radio in un unico campo)
* Piccole correzioni al codice

#### v0.19.12 - 19 Giugno 2017
* Risolto un problema quando la classe veniva instanziata più di una volta

#### v0.19.1 - 14 Giugno 2017
* Aggiunto un parametro di tipo booleano nel metodo 'returnAllMeta($includeAllPosts = false)'. Se impostato a true, forzerà il metodo a ritornare con tutti i post

#### v0.19 - 14 Giugno 2017
* Aggiuno nuovo campo input checkbox (è possibile inserire più checkbox in un unico campo)

#### v0.18.1 - 12 Giugno 2017
* Risolto un problema con il metodo 'setFeatureSupport()'

#### v0.18 - 7 Giugno 2017
* Classe rinominata in 'MetaBoxesHandler'
* Novità: i metabox verranno creati automaticamente dalla classe con il metodo 'add()'
* Cambiato il nome del metodo 'addMetaFields()' in 'add()'. Quando verrà lanciato creerà direttamente il metabox con la lista dei campi richiesti.
* Il metodo 'returnAllMeta()' ora funzionerà soltanto lato frontend. Nel backend non occorrerà più utilizzarlo
* Riscritti alcuni metodi per un utilizzo migliore della classe
* Risolto un problema nello script JS della classe
* Aggiunta la possibilità di gestire un campo del form come codice html personalizzato

#### v0.15 - 5 Giugno 2017
* Il metodo 'addMetaBoxWPImageUpload()' ora è diventato protetto ed è richiamabile automaticamente dalla classe
* Aggiunto il supporto per l'immagine in evidenza (nativa di Wordpress). Il metodo pubblico da richiamare è 'addFeatureSupport()'

#### v0.14 - 30 Maggio 2017
* Aggiunta la possibilità di rendere obbligatori i campi del form. È necessario aggingere il parametro "'required' => true" nella lista opzioni

#### v0.13 - 26 Maggio 2017
* Risolti alcuni problemi lato backend
* Aggiunto nuovo campo select (supporto anche per le opzioni di gruppo)
* Aggiunta la possibilità di aggiungere più postmeta in una singola opzione

#### v0.12 - 24 Maggio 2017
* Aggiunto campo per la gestione delle immagini, utilizzando le funzioni native di wordpress
* Aggiunta documentazione alla classe

#### v0.1 - 20 Maggio 2017
* Prima release