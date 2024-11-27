{strip}
<div style="display: block">
	<div style="object-fit: contain">
		<img class="img-responsive" src="/year_in_review/images/{$slideInfo->background_lg}" alt="Year in review background"/>
	</div>
	{foreach from=$slideInfo->overlay_text item=textInfo}
		<div style="display: block;
				position: absolute;
				top:{$textInfo->top};
				left:{$textInfo->left};
				width:{$textInfo->width};
				height:{$textInfo->height};
				color:{$textInfo->color};
				font-size:{$textInfo->font_size};
				text-align:{$textInfo->align}">
			{$textInfo->text}
		</div>
	{/foreach}
</div>
{/strip}