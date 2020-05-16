<?php

defined('JPATH_BASE') or die;
require_once JPATH_SITE . '/plugins/fabrik_element/slider/slider.php';

$d = $displayData;
// dump($displayData,"displayData");
// dump($x, "params");
?>
<div id="<?php echo $d->id; ?>" class="fabrikSubElementContainer">
<?php
	if ($d->showNone) :
		if ($d->j3) :?>
		<button class="btn btn-mini clearslider pull-left" style="margin-right:10px"><?php echo FabrikHelperHTML::icon('icon-remove'); ?></button>
		<?php
		else:
			?>
		<div class="clearslider_cont">
			<img src="<?php echo $d->outSrc; ?>" style="cursor:pointer;padding:3px;"
				alt="<?php echo FText::_('PLG_ELEMENT_SLIDER_CLEAR'); ?>" class="clearslider" />
		</div>
		<?php
		endif;
	endif;
?>

	<div class="slider_cont" style="width:<?php echo $d->width; ?>px; direction: ltr;margin-bottom: 30px">
		<input type="range" data-rangeslider class="fabrikslider-line" max="<?php echo $d->max?>" min="<?php echo $d->min?>" step="<?php echo $d->step?>">
			<div class="knob"></div>

		<?php
		if (count($d->labels) > 0 && $d->labels[0] !== '') : ?>
		<ul class="slider-labels" style="width:<?php echo $d->width; ?>px;margin-top: 15px !important;">
			<?php
			for ($i = 0; $i < count($d->labels); $i++) :
				?>
				<li style="width:<?php echo $d->spanWidth;?>px;text-align:<?php echo $d->align[$i]; ?>"><?php echo $d->labels[$i]; ?></li>
			<?php
			endfor;
			?>
			</ul>
		<?php
		endif;
		?>
		</div>
    <input type="hidden"  class="fabrikinput Frangeslider" name="<?php echo $d->name; ?>" value="<?php echo $d->value; ?>" />
		<span class="slider_output badge badge-info" style="display:none;"><?php echo $d->value;?></span>
		<output class="slider_output badge badge-info" style="width: auto;margin: 0px;"><?php echo $d->value;?></output>
	</div>
  
  <script>
    jQuery(function() {
      // console.log("hi");
            
            var $document = $(document);
            var selector = '[data-rangeslider]';
            var $element = jQuery(selector);

            // For ie8 support
            var textContent = ('textContent' in document) ? 'textContent' : 'innerText';

            // Example functionality to demonstrate a value feedback
            function valueOutput(element) {
                var value = element.value;
                
                var output = element.parentNode.getElementsByTagName('output')[0] || element.parentNode.parentNode.getElementsByTagName('output')[0];
                var span = element.parentNode.getElementsByTagName('span')[0] || element.parentNode.parentNode.getElementsByTagName('span')[0];
                // console.log(span);
                output.innerText = value;
                span.innerText = value;
                var hiddenInput = element.parentNode.getElementsByClassName("Frangeslider")[0] || element.parentNode.parentNode.getElementsByClassName("Frangeslider")[0];
                // console.log(hiddenInput);
                hiddenInput.value = value;
                // output[textContent] = value;
            }
            var dives = jQuery('div[id^=js-rangeslider]');
            var len = dives.length;
            // console.log(dives.length);
            jQuery('div[id^=js-rangeslider]').load(function(){
              alert("div loaded.");
            });
            jQuery(document).on('input', 'input[type="range"], ' + selector, function(e) {
                // console.log('salam');
                valueOutput(e.target);
            });

            // Example functionality to demonstrate disabled functionality
            jQuery(document) .on('click', '#js-example-disabled button[data-behaviour="toggle"]', function(e) {
                var $inputRange = $(selector, e.target.parentNode);

                if ($inputRange[0].disabled) {
                    $inputRange.prop("disabled", false);
                }
                else {
                    $inputRange.prop("disabled", true);
                }
                $inputRange.rangeslider('update');
            });

            // Example functionality to demonstrate programmatic value changes
            jQuery(document).on('click', '#js-example-change-value button', function(e) {
                var $inputRange = $(selector, e.target.parentNode);
                var value = $('input[type="number"]', e.target.parentNode)[0].value;

                $inputRange.val(value).change();
            });

            // Example functionality to demonstrate programmatic attribute changes
            jQuery(document).on('click', '#js-example-change-attributes button', function(e) {
                var $inputRange = $(selector, e.target.parentNode);
                var attributes = {
                        min: $('input[name="min"]', e.target.parentNode)[0].value,
                        max: $('input[name="max"]', e.target.parentNode)[0].value,
                        step: $('input[name="step"]', e.target.parentNode)[0].value
                    };

                $inputRange.attr(attributes);
                $inputRange.rangeslider('update', true);
            });

            // Example functionality to demonstrate destroy functionality
            jQuery(document)
                .on('click', '#js-example-destroy button[data-behaviour="destroy"]', function(e) {
                    $(selector, e.target.parentNode).rangeslider('destroy');
                })
                .on('click', '#js-example-destroy button[data-behaviour="initialize"]', function(e) {
                    $(selector, e.target.parentNode).rangeslider({ polyfill: false });
                });

            // Example functionality to test initialisation on hidden elements
            jQuery(document)
                .on('click', '#js-example-hidden button[data-behaviour="toggle"]', function(e) {
                    var $container = $(e.target.previousElementSibling);
                    $container.toggle();
                });

            // Basic rangeslider initialization
            // console.log("element is: ");
            // console.log($element);
            $element.rangeslider({
                // Deactivate the feature detection
                polyfill: false,
                rangeClass: 'rangeslider',
                fillClass: 'rangeslider__fill',
                handleClass: 'rangeslider__handle',

                // Callback function
                onInit: function() {
                    // valueOutput(this.$element[0]);
                },

                // Callback function
                onSlide: function(position, value) {
                    // console.log('onSlide');
                    // console.log('position: ' + position, 'value: ' + value);
                },

                // Callback function
                onSlideEnd: function(position, value) {
                    // console.log('onSlideEnd');
                    // console.log('position: ' + position, 'value: ' + value);
                }
            });

        });
        var $jq = jQuery.noConflict();
        $jq(document).ready(function(e) { 
          // alert(<?php echo $d->value == null ? 0 : $d->value ?>);
          spans = $jq("span.slider_output");
          inputs =$jq('input[type="range"]');
          xx = <?php echo '"';echo "{$d->id}";echo '"'; ?>;
          // console.log(xx);
          thisInput = $jq("#" + xx + " > div > input")[0];
          val = <?php echo '"';echo "{$d->value}";echo '"'; ?>;
          minVal = <?php echo '"';echo "{$d->min}";echo '"'; ?>;
          val = (val === "" ? minVal : val);
          thisInput.value = (val === "" ? 0 : val);
          inputs.change();
          // for(i = 0 ; i < spans.length; i++){
            // console.log("***"+i+"***");
            // spanValue = $jq("span.slider_output")[i].textContent;
            // console.log(spanValue);
            // if(spanValue == "")
              // spanValue = 0;
            // inputs[i].value = <?php echo $d->value;?>;
            // inputs.change();
            // alert(<?php echo 3;?>);
          // }
          
          // $jq('img.img-responsive').load("/", function(){
            // alert("hi");
          // });
        });
    </script>
