<?php

class StatusHelper extends AppHelper{

	// how many levels has the arrayToTable renderer recursed? we us this to only render a caption if the table has rendered one level deep or more
	private $render_flags = array(
		'arraytotable'=>array(
			'recursion'=>false,
			'level'=>1
		)
	);

    // TODO: this belongs in a helper
    public function arrayToTable($data,$options=array()){

        $options += array(
            'title' => '', // set this to have a table caption
            'row_label'=>'th'
        );

        $html = '';

        $can_render_table = (
            is_array($data)
            && !empty($data)
        ) ? true : false;

        if($can_render_table){
            // start table tag markup
            $html .= '<table  cellpadding="0" cellspacing="0" class="table table-striped">';
            // do we need to add a caption?
            $have_caption = (!empty($options['title'])) ? true : false;
            $need_caption = ($this->render_flags['arraytotable']['recursion'] === true) ? true : false;
            if($have_caption && $need_caption){
                $html .= sprintf('<caption class="small">%s</caption>',$options['title']);
            }

            foreach($data as $label => $value){
                $is_data = is_array($value);
                // if the value is itself an array then decend into it and create a table
                // $mssage = __('StatusController::arrayToTable() rendering with value %s',print_r(array(
                // 	'$data'=>$data,
                // 	'$label'=>$label,
                // 	'$value'=>$value,
                // ),true));
                // CakeLog::write('error',$mssage);
                // do we need to recures into the $data? override value if we do
                if ($is_data) {
                    $this->render_flags['arraytotable']['level'] += 1;
                    $this->render_flags['arraytotable']['recursion'] = true;
                    $value = $this->arrayToTable($value,array(
                        'title'=>$label,
                        'row_label' => 'th',
                    ));
                }else{
                    // FIXME: this is not setting the proper colspan level. instead we need to pass down a
                    // colspan level or autodetects a recursion level based on autodetecting
                    // $this->render_flags[$label][$value][$label][$value] depth
                    $this->render_flags['arraytotable']['level'] = 1;
                }
                $need_label = !$is_data;

                $colspan = '';
                // FIXME: remove || true debugging statement
                $need_colspan = ($this->render_flags['arraytotable']['level'] > 0 || true) ? true : false;
                if($need_colspan){
                    $colspan = sprintf(' colspan="%s"',$this->render_flags['arraytotable']['level']);
                }

                $html .= strtr("<tr>\n\t!label\n\t<td!colspan>!value</td>\n</tr>",array(
                    '!label'=>($need_label) ? strtr('<!type!colspan>!label</!type>',array(
                        '!type'=>$options['row_label'],
                        '!label'=>$label,
                        '!colspan'=>$colspan
                    )) : '',
                    '!value'=>$value,
                    '!colspan'=>$colspan,
                ));
            }
            // finish table tag markup
            $html .= '</table>';
        }
        return $html;
    }

    public function render(&$data){

        $have_processors = (
            isset($data['render'])
            && !empty($data['render'])
        ) ? true : false;

        $have_providers = (
            isset($data['providers'])
            && !empty($data['providers'])
        ) ? true : false;

        if($have_processors && $have_providers){

            switch ($data['render']['type']){
                case 'index':
                    // a list of renderers (cakephp view elements) that each provider will be passed to
                    $providers =& $data['providers'];
                    $processors =& $data['render']['processors'];
                    $this->renderIndex($providers,$processors);
                break;
            }
        }
        // we're finished using the render array. destroy it
        unset($data['render']);
    }

    private function renderIndex(&$providers,$processor){
        foreach($providers as &$provider){
            foreach($processor as $name){
                // pass the $provider to an element that will figure out how to render the $provider data
                $element_name = 'Api.'.$name;
                $provider = $this->_View->element($element_name,array('provider'=>$provider));
            }
        }
    }
}