<?php

namespace MediaWiki\Extension\WRNewArticles;

use DatabaseLogEntry;
use ExtensionRegistry;
use LogEventsList;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\ArticleContentArea\ArticleContentArea;
use MediaWiki\Extension\ArticleType\ArticleType;
use MediaWiki\Html\Html;
use MediaWiki\Pager\LogPager;
use MediaWiki\Title\TitleFactory;

/**
 * @ingroup SpecialPage Pager
 */
class NewArticlesPager extends LogPager {

	private TitleFactory $titleFactory;

	/** @var string|null Content area filter value */
	protected $contentArea;

	/**
	 * @param IContextSource $context
	 * @param string|null $contentArea
	 * @param TitleFactory|null $titleFactory
	 */
	public function __construct(
		IContextSource $context,
		$contentArea = null,
		?TitleFactory $titleFactory = null
	) {
		$loglist = new LogEventsList( $context, null, 0 );
		$extraConds = [
			'log_namespace' => NS_WR_DRAFTS
		];

		$this->contentArea = $contentArea;
		$this->titleFactory = $titleFactory ?? \MediaWiki\MediaWikiServices::getInstance()->getTitleFactory();

		parent::__construct(
			$loglist,
			[ 'move' ],
			'', '', '',
			$extraConds
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getQueryInfo() {
		$info = parent::getQueryInfo();

		if ( ExtensionRegistry::getInstance()->isLoaded( 'ArticleContentArea' ) ) {
			$contentAreaQuery = ArticleContentArea::getJoin( $this->contentArea, 'log_page' );
			$info = array_merge_recursive( $info, $contentAreaQuery );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'ArticleType' ) ) {
			$articleTypeQuery = ArticleType::getJoin( null, 'log_page' );
			$info = array_merge_recursive( $info, $articleTypeQuery );
		}

		return $info;
	}

	/**
	 * @inheritDoc
	 */
	public function formatRow( $row ) {
		$time = htmlspecialchars(
			$this->getLanguage()->userDate(
				$row->log_timestamp,
				$this->getUser()
			)
		);

		// Let core tell serialized params from the legacy newline-separated format, as
		// LogEventsList does: it does so without a PHP warning and with no class instances.
		$logEntry = DatabaseLogEntry::newFromRow( $row );
		$params = $logEntry->getParameters();
		$targetName = $logEntry->isLegacy() ? ( $params[0] ?? null ) : ( $params['4::target'] ?? null );
		$logPageId = isset( $row->log_page ) ? (int)$row->log_page : 0;
		$currentTitle = ( $logPageId !== 0 ) ? $this->titleFactory->newFromID( $logPageId ) : null;

		// The target may not parse as a title (e.g. it was logged under title rules that
		// have since changed, or the params lack a target); such a row cannot be linked.
		$targetTitleObj = is_string( $targetName ) ? $this->titleFactory->newFromText( $targetName ) : null;

		if ( !$targetTitleObj || $targetTitleObj->getNamespace() !== NS_MAIN ) {
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

		// article_type is only selected when ArticleType is loaded (see getQueryInfo())
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ArticleType' ) ) {
			$articleTypeReadable = ArticleType::getReadableArticleTypeFromCode( $row->article_type ?? null );
			$articleTypeDisplay = ' ' . $this->msg( 'newarticles-articletype' )
				->params( $articleTypeReadable )->text();
		}

		return Html::rawElement(
			'li',
			[],
			"{$time}: {$pageLink}{$articleTypeDisplay}{$originalNameDisplay}"
		) . "\n";
	}
}
