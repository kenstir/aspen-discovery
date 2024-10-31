{strip}
{* Add availability as needed *}
{if !empty($showAvailability) && $availability}
	<div>
		<table class="holdingsTable table table-striped table-responsive">
			<thead>
				<tr>
					<th>{translate text="Collection" isPublicFacing=true}</th>
					<th>{translate text="Owned" isPublicFacing=true}</th>
					<th>{translate text="Available" isPublicFacing=true}</th>
					<th>{translate text="Number of Holds" isPublicFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$availability item="availabilityRow"}
					<tr>
						<td>{$availabilityRow->getSettingName()}</td>
						<td>{if $availabilityRow->copiesOwned > 9999}{translate text="Always Available" isPublicFacing=true}{else}{$availabilityRow->copiesOwned}{/if}</td>
						<td>{if $availabilityRow->copiesOwned <= 9999}{$availabilityRow->copiesAvailable}{/if}</td>
						<td>{if $availabilityRow->copiesOwned <= 9999}{$availabilityRow->numberOfHolds}{/if}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/if}
{/strip}