<?php

/**
 * Special page to display list of pages moved from NS_DRAFT to NS_MAIN
 *
 */
class SpecialNewArticles extends SpecialPage {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( 'NewArticles' );
	}

	protected function getGroupName() {
		return 'changes';
	}

	function execute( $query ) {
		$this->setHeaders();

		$pager = new NewArticlesPager();

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

}

/**
 * @ingroup SpecialPage Pager
 */
class NewArticlesPager extends LogPager {
	function __construct() {
		$loglist = new LogEventsList(
			$this->getContext(),
			null,
			0
		);
		$extraConds = [
			'log_namespace' => NS_WR_DRAFTS
		];

		parent::__construct(
			$loglist,
			[ 'move' ],
			'', '', '',
			$extraConds
		);

	}

	public function getQueryInfo() {
		$info = parent::getQueryInfo();
		$info[ 'fields' ][] = 'log_page';

		// if Extension:WRArticleType is available
		if ( class_exists( 'WRArticleType' ) ) {
			$info[ 'tables' ][] = 'page_props';
			$info[ 'join_conds' ][ 'page_props' ] = [
				'LEFT JOIN',
				[
					'pp_page=log_page',
					'pp_propname = "ArticleType"'
				]
			];

			$info[ 'fields' ][] = 'pp_value';
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

		$pageLink = Linker::link( $originalNameDisplay ? $currentTitle : $targetTitleObj );
		$articleTypeDisplay = '';

		if ( isset( $row->pp_value ) && $row->pp_value === 'portal' ) {
			$pageLink = Html::rawElement( 'strong', [], $pageLink );

			$articleTypeReadable = WRArticleType::getReadableArticleTypeFromCode( $row->pp_value );
			$articleTypeDisplay = $this->msg( 'newarticles-articletype' )
									->params( $articleTypeReadable )->text();
			$articleTypeDisplay = ' ' . $articleTypeDisplay;
		}



		$formattedRow = Html::rawElement(
			'li',
			[],
			"{$time}: {$pageLink}{$articleTypeDisplay}{$originalNameDisplay}"
		) . "\n";

		return $formattedRow;
	}

}
