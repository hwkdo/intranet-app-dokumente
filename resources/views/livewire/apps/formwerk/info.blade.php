<?php

use function Livewire\Volt\{title};

title('Formwerk - App-Info');

?>

<x-intranet-app-formwerk::formwerk-layout heading="App-Info" subheading="Installierte Version und Release-Historie">
    @livewire('intranet-app-base::app-info', ['appIdentifier' => 'formwerk'])
</x-intranet-app-formwerk::formwerk-layout>
