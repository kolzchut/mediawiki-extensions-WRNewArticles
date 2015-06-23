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
		$request = $this->getRequest();
		$output = $this->getOutput();

		$this->setHeaders();

		$pager = new NewArticlesPager();

		//log_namespace =

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
			'log_namespace = ' . NS_WR_DRAFTS
		);

		parent::__construct(
			$loglist,
			array( 'move' ),
			null, null, null,
			$extraConds
		);


	}

	function formatRow( $row ) {
		$time = htmlspecialchars(
			$this->getLanguage()->userDate(
				$row->log_timestamp,
				$this->getUser()
			)
		);

		$params = unserialize( $row->log_params );
		//print_r( $params ); echo "\n";
		$newName = $params['4::target'];
		$pageTitle = Title::newFromText( $newName );

		// Make sure the target is NS_MAIN
		if ( $pageTitle->getNamespace() !== NS_MAIN ) {
			return false;
		}

		$pageLink = Linker::linkKnown( $pageTitle );
		$formattedRow = Html::rawElement(
			'li',
			array(),
			"$time: $pageLink"
		) . "\n";

		return $formattedRow;
	}

}
