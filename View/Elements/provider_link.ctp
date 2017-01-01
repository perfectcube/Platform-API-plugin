<?php
echo $this->Html->link($provider, array(
    'controller' => 'status',
    'action' => 'check',
    $provider,
    'full_base' => true
));