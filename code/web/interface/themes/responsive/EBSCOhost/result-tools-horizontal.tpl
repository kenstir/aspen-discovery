{strip}
	{if $showEmailThis || $showShareOnExternalSites}
		<div class="result-tools-horizontal btn-toolbar" role="toolbar">
			{* More Info Link, only if we are showing other data *}
			{if !empty($showMoreInfo)}
				{if $showMoreInfo !== false}
					<div class="btn-group btn-group-sm">
						<a href="{if !empty($summUrl)}{$summUrl}{else}{$recordDriver->getLinkUrl()}{/if}" class="btn btn-sm btn-tools" onclick="AspenDiscovery.EBSCO.trackEdsUsage('{$recordDriver->getPermanentId()}')" target="_blank" aria-label="{translate text="More Info" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="More Info" isPublicFacing=true}</a>
					</div>
				{/if}
				{if $showFavorites == 1 && (empty($offline) || $enableEContentWhileOffline)}
					<div class="btn-group btn-group-sm">
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Ebscohost', '{$recordDriver->getPermanentId()|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
					</div>
				{/if}
			{/if}

			<div class="btn-group btn-group-sm">
				{include file="EBSCOhost/share-tools.tpl"}
			</div>
		</div>
	{/if}
{/strip}