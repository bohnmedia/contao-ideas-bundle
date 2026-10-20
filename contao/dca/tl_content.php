<?php

/*
 * Palette für das Inhaltselement "Ideenliste"
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['ini_idea_list'] = '{type_legend},type,headline;{iniIdea_legend},perPage,iniIdeaAllowWithdraw;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

/*
 * Zusätzliche Felder
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaAllowWithdraw'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50 m12'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);
