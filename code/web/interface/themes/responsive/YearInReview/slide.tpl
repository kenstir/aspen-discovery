{strip}
<div style="display: block; text-align: center">
	{if empty($slideInfo->overlay_text)}
		<img class="img-responsive" src="/year_in_review/images/{$slideInfo->background}" alt="Year in review background"/>
	{else}
		<img class="img-responsive" src="/MyAccount/AJAX?method=getYearInReviewSlideImage&slide={$slideNumber}" alt="Year in review background"/>
	{/if}
</div>
{/strip}