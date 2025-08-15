<?php

namespace WooAssistant\Providers\UI;

use WooAssistant\Helper\Templates;

abstract class AbstractDataTableUI {
	/**
	 * @var string Table id
	 */
	private string $id = '';

	/**
	 * @var int ID field
	 */
	private $idField = 'id';

	/**
	 * @var string Table title
	 */
	private string $title = '';

	/**
	 * @var string Table description
	 */
	private string $description = '';

	/**
	 * @var string Add new button text
	 */
	private string $addNewButton = '';

	/**
	 * @var string Modal add title
	 */
	private string $modalAddTitle = '';

	/**
	 * @var string Modal edit title
	 */
	private string $modalEditTitle = '';

	/**
	 * @var string Modal add new button text
	 */
	private string $modalAddNewButton = '';

	/**
	 * @var string Modal edit new button text
	 */
	private string $modalEditButton = '';

	/**
	 * @var array Table columns
	 */
	private array $columns = [];

	/**
	 * @var array Table rows
	 */
	private array $rows = [];

	/**
	 * @var int Row count
	 */
	private int $rowCount = 0;

	/**
	 * @var bool Display Row count
	 */
	private bool $displayRowCount = true;

	/**
	 * @var bool Default active field value
	 */
	private bool $activeFieldValue = true;

	/**
	 * @var bool Display active field
	 */
	private bool $displayActiveField = true;

	/**
	 * @var array Table actions
	 */
	private array $actions = [];

	/**
	 * @var array Table attributes
	 */
	private array $attributes = [];

	/**
	 * @var int Current page number
	 */
	private int $currentPage = 1;

	/**
	 * @var int Rows per page
	 */
	private int $perPage = 20;

	/**
	 * @var bool Has bulk action
	 */
	private bool $hasBulkAction = false;
	/**
	 * @var bool Display top bulk action
	 */
	private bool $topBulkAction = true;
	/**
	 * @var bool Display bottom bulk action
	 */
	private bool $bottomBulkAction = false;

	/**
	 * @var bool Display bottom bulk action
	 */
	private bool $sortable = false;

	/**
	 * @const Row index constant
	 */
	public const ROW_INDEX = '__INDEX__';
	/**
	 * @const Row number constant
	 */
	public const ROW_NUMBER = '__NUMBER__';

	/**
	 * @const Action none
	 */
	public const ACTION_NONE = 0;
	/**
	 * @const Action single
	 */
	public const ACTION_SINGLE = 1;
	/**
	 * @const Action single
	 */
	public const ACTION_BULK = 2;

	/**
	 * @const Delete action type
	 */
	public const ACTION_DELETE = 'delete';
	/**
	 * @const Edit action type
	 */
	public const ACTION_EDIT = 'edit';

	/**
	 * @const Active field
	 */
	public const ACTIVE_FIELD = 'is_active';

	public function setID( $id ): AbstractDataTableUI {
		$this->id = $id;

		return $this;
	}

	public function setIdField( $fieldName ): AbstractDataTableUI {
		$this->idField = $fieldName;

		return $this;
	}

	public function setTitle( $title ): AbstractDataTableUI {
		$this->title = $title;

		return $this;
	}

	public function setDesc( $description ): AbstractDataTableUI {
		$this->description = $description;

		return $this;
	}

	public function addNewButton( $text ): AbstractDataTableUI {
		$this->addNewButton = $text;

		return $this;
	}

	public function modalAddTitle( $text ): AbstractDataTableUI {
		$this->modalAddTitle = $text;

		return $this;
	}

	public function modalEditTitle( $text ): AbstractDataTableUI {
		$this->modalEditTitle = $text;

		return $this;
	}

	public function modalAddNewButton( $text ): AbstractDataTableUI {
		$this->modalAddNewButton = $text;

		return $this;
	}

	public function modalEditButton( $text ): AbstractDataTableUI {
		$this->modalEditButton = $text;

		return $this;
	}

	public function setAttributes( $attributes ): AbstractDataTableUI {
		$this->attributes = $attributes;

		return $this;
	}

	public function displayTopBulkAction( $display ): AbstractDataTableUI {
		$this->topBulkAction = $display;

		return $this;
	}

	public function displayBottomBulkAction( $display ): AbstractDataTableUI {
		$this->bottomBulkAction = $display;

		return $this;
	}

	public function sortable( $enable ): AbstractDataTableUI {
		$this->sortable = $enable;

		return $this;
	}

	public function setRows( $data ): AbstractDataTableUI {
		if ( is_array( $data ) ) {
			$this->rows     = $data;
			$this->rowCount = count( $data );
		}

		return $this;
	}

	public function getRowCount(): int {
		return $this->rowCount;
	}

	public function setDisplayRowCount( $display ): AbstractDataTableUI {
		$this->displayRowCount = (bool) $display;

		return $this;
	}

	public function setDisplayActiveField( $display ): AbstractDataTableUI {
		$this->displayActiveField = (bool) $display;

		return $this;
	}

	/**
	 * @param bool $value Active status
	 *
	 * @return $this
	 */
	public function setActiveField( bool $value ): AbstractDataTableUI {
		$this->activeFieldValue = $value;

		return $this;
	}

	public function setPerPage( $perPage ): AbstractDataTableUI {
		$this->perPage = $perPage;

		return $this;
	}

	public function addColumn( $name, $field, $columnData = null, $args = [] ): AbstractDataTableUI {
		$defaultArgs = [
			'is_sortable'    => false,
			'is_shown'       => true,
			'type'           => 'text',
			'is_html'        => false,
			'hide_on_mobile' => false,
		];

		$args['name']        = $name;
		$args['field']       = $field;
		$args['column_data'] = is_null( $columnData ) ? $field : $columnData;

		$this->columns[] = wp_parse_args( $args, $defaultArgs );

		return $this;
	}

	public function addAction( $key, $title, $type, $attributes = [], $flag = self::ACTION_SINGLE ): AbstractDataTableUI {
		$this->actions[ $key ] = [
			'key'        => $key,
			'title'      => $title,
			'type'       => $type,
			'attributes' => is_array( $attributes ) ? $attributes : [],
			'flag'       => $flag
		];

		if ( $flag === self::ACTION_BULK ) {
			$this->hasBulkAction = true;
		}

		return $this;
	}

	private function getThead(): array {
		$thead = [];

		foreach ( $this->columns as $column ) {
			if ( ! $column['is_shown'] ) {
				continue;
			}

			$thead[ $column['field'] ] = [
				'name'           => $column['name'],
				'field'          => $column['field'],
				'is_sortable'    => $column['is_sortable'],
				'order_by_field' => $column['is_sortable'] ? $column['order_by_field'] : false,
				'hide_on_mobile' => $column['hide_on_mobile'],
			];
		}

		return $thead;
	}

	private function getTbody(): array {
		$data  = [];
		$index = ( $this->currentPage - 1 ) * $this->perPage;

		if ( empty( $this->rows ) ) {
			return $data;
		}

		$rows = array_slice( $this->rows, 0, $this->perPage );

		foreach ( $rows as $row ) {
			$newRow = [];

			foreach ( $this->columns as $column ) {
				if ( ! $column['is_shown'] ) {
					continue;
				}

				if ( is_callable( $column['column_data'] ) && is_object( $column['column_data'] ) ) {
					$columnData = $column['column_data']( $row );

				} else if ( $column['column_data'] === static::ACTIVE_FIELD ) {
					$columnData = (int) $row[ $column['column_data'] ];

				} else if ( is_string( $column['column_data'] ) && isset( $row[ $column['column_data'] ] ) ) {
					$columnData = $row[ $column['column_data'] ];

					if ( isset( $column['type'] ) ) {
						$columnData = $this->columnTypeFilter( $columnData, $column['type'] );
					}

				} else if ( $column['column_data'] === static::ROW_INDEX ) {
					$columnData = $index;

				} else if ( $column['column_data'] === static::ROW_NUMBER ) {
					$columnData = $index + 1;

				} else {
					$columnData = '-';
				}

				if ( $column['is_html'] == false ) {
					$columnData = htmlspecialchars( $columnData, ENT_QUOTES );
				}

				$attributes = [];

				if ( isset( $column['attr'] ) ) {
					$attributes = $column['attr'];
				}

				$newRow[] = [
					'field'      => $column['field'],
					'content'    => $columnData,
					'attributes' => $attributes
				];
			}

			$data[] = [
				'id'        => $this->idField === static::ROW_INDEX ? $index : $row[ $this->idField ],
				'data'      => $newRow,
				'is_active' => isset( $row['is_active'] ) && ( int ) $row['is_active'] === 1
			];

			$index ++;
		}

		return $data;
	}

	private function columnTypeFilter( $data, $type ) {
		switch ( $type ) {
			case 'datetime':
				return empty( $data ) ? '' : wp_date( 'Y-m-d H:i:s', $data );
			case 'date':
				return empty( $data ) ? '' : wp_date( 'Y-m-d', $data );
			case 'time':
				return empty( $data ) ? '' : wp_date( 'H:i:s', $data );
			case 'number':
				return empty( $data ) ? '' : number_format( $data );
			case 'int':
				return (int) $data;
			default:
				return $data;
		}
	}

	public function render(): array {
		return array(
			'id'                   => $this->id,
			'title'                => $this->title,
			'description'          => $this->description,
			'add_new_button'       => $this->addNewButton,
			'thead'                => $this->getThead(),
			'tbody'                => $this->getTbody(),
			'actions'              => $this->actions,
			'has_bulk_action'      => $this->hasBulkAction,
			'top_bulk_action'      => $this->topBulkAction,
			'bottom_bulk_action'   => $this->bottomBulkAction,
			'attributes'           => $this->attributes,
			'current_page'         => $this->currentPage,
			'per_page'             => $this->perPage,
			'display_row_count'    => $this->displayRowCount,
			'row_count'            => $this->rowCount,
			'active_field'         => $this->activeFieldValue,
			'display_active_field' => $this->displayActiveField,
			'sortable'             => $this->sortable,
			'modal_add_title'      => empty( $this->modalAddTitle ) ? $this->title : $this->modalAddTitle,
			'modal_edit_title'     => empty( $this->modalEditTitle ) ? $this->title : $this->modalEditTitle,
			'modal_add_button'     => empty( $this->modalAddNewButton ) ? __( 'Add new', 'wc-assistant' ) : $this->modalAddNewButton,
			'modal_edit_button'    => empty( $this->modalEditButton ) ? __( 'Save changes', 'wc-assistant' ) : $this->modalEditButton,
		);
	}

	public function renderHTML( $view = null ) {
		$data     = $this->render();
		$template = empty( $view ) ? Templates::getPath( 'data-table/data_table.php' ) : $view;

		return Templates::load( $template, $data, false, false );
	}
}