﻿MetaBoxesHandler

=== CHANGELOG ===

[12 Giugno 2017] versione 0.18.1
- Risolto un problema con il metodo 'setFeatureSupport()'

[7 Giugno 2017] versione 0.18
- Classe rinominata in 'MetaBoxesHandler'
- Novità: i metabox verranno creati automaticamente dalla classe con il metodo 'add()'
- Cambiato il nome del metodo 'addMetaFields()' in 'add()'. Quando verrà lanciato creerà direttamente il metabox con la lista dei campi richiesti.
- Il metodo 'returnAllMeta()' ora funzionerà soltanto lato frontend. Nel backend non occorrerà più utilizzarlo
- Riscritti alcuni metodi per un utilizzo migliore della classe
- Risolto un problema nello script JS della classe
- Aggiunta la possibilità di gestire un campo del form come codice html personalizzato

[5 Giugno 2017] versione 0.15
- Il metodo 'addMetaBoxWPImageUpload()' ora è diventato protetto ed è richiamabile automaticamente dalla classe
- Aggiunto il supporto per l'immagine in evidenza (nativa di Wordpress). Il metodo pubblico da richiamare è 'addFeatureSupport()'

[30 Maggio 2017] versione 0.14
- Aggiunta la possibilità di rendere obbligatori i campi del form. È necessario aggingere il parametro "'required' => true" nella lista opzioni

[26 Maggio 2017] versione 0.13
- Risolti alcuni problemi lato backend
- Aggiunto nuovo campo select (supporto anche per le opzioni di gruppo)
- Aggiunta la possibilità di aggiungere più postmeta in una singola opzione

[24 Maggio 2017] versione 0.12
- Aggiunto campo per la gestione delle immagini, utilizzando le funzioni native di wordpress
- Aggiunta documentazione alla classe

[20 Maggio 2017] versione 0.1
- Prima release