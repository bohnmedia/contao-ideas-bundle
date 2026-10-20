<?php

/*
 * Palette für das Inhaltselement "Ideenliste"
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['ini_idea_list'] = '{type_legend},type,headline;{iniIdea_legend},perPage,iniIdeaShowAuthor,iniIdeaAllowWithdraw;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

/*
 * Palette für das Inhaltselement "Idee einreichen"
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['ini_idea_form'] = '{type_legend},type,headline;{iniIdeaForm_legend},iniIdeaDescriptionMandatory,iniIdeaEmailMandatory,iniIdeaPrivacy;{iniIdeaSubmit_legend},iniIdeaConfirmation,iniIdeaJumpTo,iniIdeaNotification;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'iniIdeaPrivacy';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['iniIdeaPrivacy'] = 'iniIdeaPrivacyText';

/*
 * Zusätzliche Felder der Ideenliste
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaShowAuthor'] = array
(
	'inputType'               => 'checkbox',
	'default'                 => true,
	'eval'                    => array('tl_class'=>'w50 m12'),
	'sql'                     => array('type' => 'boolean', 'default' => true)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaAllowWithdraw'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50 m12'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

/*
 * Zusätzliche Felder des Formulars
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaDescriptionMandatory'] = array
(
	'inputType'               => 'checkbox',
	'default'                 => true,
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => true)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaEmailMandatory'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaPrivacy'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaPrivacyText'] = array
(
	'inputType'               => 'textarea',
	'eval'                    => array('allowHtml'=>true, 'rows'=>3, 'tl_class'=>'clr'),
	'sql'                     => "text NULL"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaConfirmation'] = array
(
	'inputType'               => 'textarea',
	'eval'                    => array('rte'=>'tinyMCE', 'basicEntities'=>true, 'tl_class'=>'clr'),
	'sql'                     => "text NULL"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaJumpTo'] = array
(
	'inputType'               => 'pageTree',
	'foreignKey'              => 'tl_page.title',
	'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr w50'),
	'sql'                     => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
	'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_content']['fields']['iniIdeaNotification'] = array
(
	'inputType'               => 'select',
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => array('type' => 'integer', 'unsigned' => true, 'default' => 0)
);
