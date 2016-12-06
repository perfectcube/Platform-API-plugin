<?php 

// if we have render instructions process them
$this->Status->render($result['data']);
// pass the data to the table renderer
$markup = $this->Status->arrayToTable($result['data']);

?>
<div id="api-provider-report" class="api_provider form">
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h1><?php echo __('Api Providers'); ?></h1>
            </div>
        </div><!-- end col md 12 -->
    </div>
    <div class="row">
    <?php
    $at_index = empty($this->request->params['pass']) ? true : false;
    if (!$at_index){ ?>
        <div class="col-md-3">
            <div class="actions">
                <div class="panel panel-default">
                    <div class="panel-heading"><?php echo __('Actions'); ?></div>
                    <div class="panel-body">
                        <ul class="nav nav-pills nav-stacked">
                            <li><?php
                            echo $this->Html->link(
                                '<span class="glyphicon glyphicon-list"></span>&nbsp;&nbsp;'.__('All Providers'),
                                array(
                                    'controller'=>'status',
                                    'action' => 'check',
                                ),
                                array(
                                    'escape' => false
                                )
                            );
                            ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div><!-- end col md 3 -->
    <?php } ?>

        <div class="col-md-9">
            <p><?php echo $result['message'] ?></p>
            <?php echo $markup; ?>
        </div><!-- end col md 9 -->
    </div><!-- end row -->
</div>