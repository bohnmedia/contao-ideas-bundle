<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_ini_idea'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'        => 'primary',
				'tstamp'    => 'index',
				'createdAt' => 'index',
				'memberId'  => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('createdAt DESC'),
			'panelLayout'             => 'filter;sort,search,limit',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title', 'memberId', 'createdAt'),
			'format'                  => '%s <span class="label-info">[%s, %s]</span>'
		),
		'operations' => array
		(
			'edit',
			'toggle' => array
			(
				'href'                => 'act=toggle&amp;field=invisible',
				'icon'                => 'visible.svg',
				'showInHeader'        => true
			),
			'delete',
			'show'
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{idea_legend},memberId,createdAt,title,description;{answer_legend},answer,answerNotifiedAt;{invisible_legend},invisible'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
        'memberId' => array
        (
            'foreignKey'              => "tl_member.CONCAT(firstname,' ',lastname)",
            'filter'                  => true,
            'sorting'                 => true,
            'inputType'               => 'select',
            'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default 0",
            'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
        ),
		'createdAt' => array
		(
			'default'                 => time(),
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'title' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'long clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'description' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rows'=>8, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'answer' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'basicEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'answerNotifiedAt' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50 wizard clr'),
			// NULL = noch nicht versendet, Feld leeren erlaubt erneuten Versand
			'sql'                     => "int(10) unsigned NULL"
		),
		'invisible' => array
		(
			'reverseToggle'           => true,
			'filter'                  => true,
			'default'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => true)
		)
	)
);
