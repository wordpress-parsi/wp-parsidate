<?php

namespace WPParsidate\Block;

class Blocks {
  public function __construct() {
    new ArchiveBlock();
    new CalendarBlock();
  }
}
