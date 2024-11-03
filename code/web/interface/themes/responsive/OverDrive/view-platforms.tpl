{strip}
{if count($availablePlatforms) > 0}
	{foreach from=$availablePlatforms item=overDrivePlatform key=index}
	<div id="itemRow" class="eContentHolding">
		<div class="eContentHoldingHeader">
			<div class="row">
				<div class="col-sm-9">
					<span class="eContentHoldingFormat">{$overDrivePlatform.name}</span>
				</div>
			</div>

			<div class="row eContentHoldingUsage">
				<div class="col-sm-12">
					{$overDrivePlatform.notes}
				</div>
			</div>
		</div>
	</div>
	{/foreach}
{/if}

{/strip}