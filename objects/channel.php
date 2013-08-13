<?php
class Channel {
  
  public $ChannelID = 1;
  
  public function setChannelID($ChannelID) {
    if ($ChannelID != "") $this->ChannelID = $ChannelID;
  }
  
  public function getChannelID() {
    return $this->ChannelID;
  }


}
?>