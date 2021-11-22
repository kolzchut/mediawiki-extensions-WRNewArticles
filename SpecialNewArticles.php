<?php

use MediaWiki\Extension\ArticleContentArea\ArticleContentArea;

/**
 * Special page to display list of pages moved from NS_DRAFT to NS_MAIN
 *
 */
class SpecialNewArticles extends FormSpecialPage {
	/**
	 * Constructor
	 */
	function __construct( $name = 'NewArticles' ) {
		parent::__construct( $name );
	}

	protected function getGroupName() {
		return 'changes';
	}

	public function onSubmit( array $data ) {
		$contentarea = $this->getRequest()->getVal( 'contentarea' );

		$pager = new NewArticlesPager( $contentarea );

		# Insert list
		$logBody = $pager->getBody();
		if ( $logBody ) {
			$this->getOutput()->addHTML(
				$pager->getNavigationBar() .
				$logBody .
				$pager->getNavigationBar()
			);
		} else {
			$this->getOutput()->addWikiMsg( 'logempty' );
		}
	}

	protected function alterForm( HTMLForm $form ) {
		$form->setMethod( 'get' );
	}

	protected function getFormFields() {
		$fields = [];
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ArticleContentArea' ) ) {
			$fields['contentarea'] = [
				'type'          => 'select',
				'name'          => 'contentarea',
				'label-message' => 'newarticles-filter-contentarea',
				'options'       => self::getContentAreaOptions(),
			];
		}

		return $fields;
	}

	private static function makeOptionsForSelect( $arr ) {
		$arr = array_filter( $arr ); // Remove empty elements
		$arr = array_combine( $arr, $arr );

		return $arr;
	}

	private static function makeOptionsWithAllForSelect( $arr ) {
		$arr = [ 'הכל' => '' ] + self::makeOptionsForSelect( $arr ); // @todo i18n

		return $arr;
	}

	private static function getContentAreaOptions() {
		return self::makeOptionsWithAllForSelect( ArticleContentArea::getValidContentAreas() );
	}

	/**
	 * @inheritDoc
	 */
	protected function getDisplayFormat() {
		return 'ooui';
	}

}

/**
 * @ingroup SpecialPage Pager
 */
class NewArticlesPager extends LogPager {
	protected $contentArea;

	function __construct( $contentArea = null ) {
		$loglist = new LogEventsList(
			$this->getContext(),
			null,
			0
		);
		$extraConds = [
			'log_namespace' => NS_WR_DRAFTS
		];

		$this->contentArea = $contentArea;

		parent::__construct(
			$loglist,
			[ 'move' ],
			'', '', '',
			$extraConds
		);

	}

	public function getQueryInfo() {
		$info = parent::getQueryInfo();

		if ( ExtensionRegistry::getInstance()->isLoaded ( 'ArticleContentArea' ) ) {
			$contentAreaQuery = \MediaWiki\Extension\ArticleContentArea\ArticleContentArea::getJoin( $this->contentArea, 'log_page' );
			$info = array_merge_recursive( $info, $contentAreaQuery );
		}

		// if Extension:WRArticleType is available
		if ( ExtensionRegistry::getInstance()->isLoaded ( 'ArticleType' ) ) {
			$articleTypeQuery = \MediaWiki\Extension\ArticleType\ArticleType::getJoin( null, 'log_page' );

			$info = array_merge_recursive( $info, $articleTypeQuery );
		}

		return $info;
	}

	function formatRow( $row ) {
		$time = htmlspecialchars(
			$this->getLanguage()->userDate(
				$row->log_timestamp,
				$this->getUser()
			)
		);

		$paramsSerialized = unserialize( $row->log_params );
		$paramsOldFormat = null;
		// If the params aren't serialized, it's an older log format
		if ( $paramsSerialized === false ) {
			$paramsOldFormat = explode( "\n", $row->log_params );
		}

		$targetName = $paramsSerialized === false ? $paramsOldFormat[0] : $paramsSerialized['4::target'];
		$logPageId = (int)$row->log_page;
		$currentTitle = ( $logPageId && $logPageId !== 0 ) ? Title::newFromID( $logPageId ) : null;

		$targetTitleObj = Title::newFromText( $targetName );

		// Make sure the target is NS_MAIN
		if ( $targetTitleObj->getNamespace() !== NS_MAIN ) {
			return false;
		}

		$originalNameDisplay = '';
		if ( $currentTitle && $targetName !== $currentTitle->getFullText() ) {
			$originalNameDisplay = ' ' . $this->msg( 'newarticles-original-title' )->params( $targetName );
		}

		$pageLink = $this->getLinkRenderer()->makeLink( $originalNameDisplay ? $currentTitle : $targetTitleObj );
		$articleTypeDisplay = '';

		if ( isset( $row->article_type ) && $row->article_type === 'portal' ) {
			$pageLink = Html::rawElement( 'strong', [], $pageLink );
		}

		$articleTypeReadable = WRArticleType::getReadableArticleTypeFromCode( $row->article_type );
		$articleTypeDisplay = $this->msg( 'newarticles-articletype' )
								->params( $articleTypeReadable )->text();
		$articleTypeDisplay = ' ' . $articleTypeDisplay;

		$formattedRow = Html::rawElement(
			'li',
			[],
			"{$time}: {$pageLink}{$articleTypeDisplay}{$originalNameDisplay}"
		) . "\n";

		return $formattedRow;
	}

}
