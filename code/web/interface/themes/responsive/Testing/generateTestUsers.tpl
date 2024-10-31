{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($results)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert {if !empty($results.success)}alert-success{else}alert-danger{/if}">
					{$results.message}
				</div>
			</div>
		</div>
	{/if}
	<div class="row">
		<div class="col-xs-12">
			<div class="alert alert-info">{translate text="This tool can be used to create users that can be used for testing. Test Users are NOT added to the ILS. The tool may take several minutes to generate the users." isAdminFacing=true}</div>
		</div>
	</div>
	<form id="generateTestUsersForm" method="get" role="form">
		<div class='editor'>
			<div class="form-group">
				<label for="startingBarcode" class="control-label">{translate text='Starting Patron Barcode' isPublicFacing=true}</label>
				<input type="text" id="startingBarcode" name="startingBarcode" class="form-control" value="{$suggestedStartingBarcode}">
			</div>
			<div class="form-group">
				<label for="defaultPassword" class="control-label">{translate text='Default Password' isPublicFacing=true}</label>
				<input type="text" id="defaultPassword" name="defaultPassword" class="form-control" value="{$defaultPassword}">
			</div>


			<div class="form-group">
				<label for="numberOfUsersToGenerate" class="control-label">{translate text='Number of Patrons to Generate' isPublicFacing=true}</label>
				<input type="number" id="numberOfUsersToGenerate" name="numberOfUsersToGenerate" class="form-control" value="100" min="0" max="1000">
			</div>
			<div class="form-group">
				<button type="submit" id="generateTestUsers" name="generateTestUsers" class="btn btn-primary">{translate text="Generate Test Users" isAdminFacing=true}</button>
			</div>
		</div>
	</form>
{/strip}