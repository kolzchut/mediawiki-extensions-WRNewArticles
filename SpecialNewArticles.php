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
		$extraConds = array(
			'log_namespace' => NS_WR_DRAFTS
		);

		parent::__construct(
			$loglist,
			array( 'move' ),
			'', '', '',
			$extraConds
		);

	}

	public function getQueryInfo() {
		$info = parent::getQueryInfo();
		$info['tables'][] = 'page_props';
		$info['join_conds']['page_props'] = array( 'LEFT JOIN', array(
			'pp_page=log_page',
			'pp_propname = "ArticleType"'
		) );


		$info['fields'][] = 'pp_value';

		return $info;
	}


	/**
	 * @param array|stdClass $row
	 *
	 * @return bool|string
	 */
	function formatRow( $row ) {

		// Make sure the target is NS_MAIN, otherwise we're not interested
		if ( (int)$row->log_namespace !== NS_MAIN ) {
			return false;
		}

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

		$newMoveName = $paramsSerialized === false ? $paramsOldFormat[0] : $paramsSerialized['4::target'];
		$currentTitle = $row->log_page ? Title::newFromID( $row->log_page ) : null;

		$pageTitle = Title::newFromText( $newMoveName );



		$pageLink = Linker::link( $pageTitle );
		$articleTypeDisplay = '';

		if ( $row->pp_value === 'portal' ) {
			$pageLink = Html::rawElement( 'strong', array(), $pageLink );

			$articleTypeReadable = WRArticleType::getReadableArticleTypeFromCode( $row->pp_value );
			$articleTypeDisplay = $this->msg( 'newarticles-articletype' )->params( $articleTypeReadable )->text();
			$articleTypeDisplay = ' ' . $articleTypeDisplay . ' ';
		}

		$originalNameDisplay = '';
		if ( $newMoveName !== $currentTitle->getFullText() ) {
			$originalNameDisplay = $newMoveName;
		}


		$formattedRow = Html::rawElement(
			'li',
			array(),
			"{$time}: {$pageLink}{$articleTypeDisplay}{$originalNameDisplay}"
		) . "\n";

		return $formattedRow;
	}

}
