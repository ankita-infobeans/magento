<?php
class ICC_TrackingCode_Block_ClicktaleBottom extends Mage_Core_Block_Text
{
    protected function _toHtml()
    {
        return '
<!-- ClickTale Bottom part -->
<script type="text/javascript">
// The ClickTale Balkan Tracking Code may be programmatically customized using hooks:
//
//   function ClickTalePreRecordingHook() { /* place your customized code here */  }
//
// For details about ClickTale hooks, please consult the wiki page http://wiki.clicktale.com/Article/Customizing_code_version_2
document.write(unescape("%3Cscript%20src=\'"+
(document.location.protocol=="https:"?
"https://cdnssl.clicktale.net/www02/ptc/30baf4b0-38b2-458c-a385-557487cc6144.js":
"http://cdn.clicktale.net/www02/ptc/30baf4b0-38b2-458c-a385-557487cc6144.js")+"\'%20type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<!-- ClickTale end of Bottom part -->';
    }
}