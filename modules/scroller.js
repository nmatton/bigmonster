// Scroller module for implementing scrollmaps
// Last update: 26 Apr 2021

// === CSS FILE MUST CONTAIN ===
//    /** Scrollable area **/
//    .scrollerClass {
//        position: relative;
//        width: 100%;
//        height: 400px;
//        overflow: hidden;
//        background-color: AliceBlue;
//    }
//    .scroller_noclick, .scroller_clickable {
//        position: absolute;
//        top: 205px;
//        left:  315px;
//    }
//    .scroller_surface {
//        position: absolute;
//        top: 0px;
//        left: 0px;
//        width: 100%;
//        height: 100%;
//        cursor: move;
//    }
//    .movetop,.moveleft,.moveright,.movedown {
//        display: block;
//        position: absolute;
//        background-image: url('../../../img/common/arrows.png');
//        width: 32px;
//        height: 32px;
//    }
//    .movetop {
//        top: 0px;
//        left: 50%;
//        background-position: 0px 32px;
//    }
//    .moveleft {
//        top: 50%;
//        left: 0px;
//        background-position: 32px 0px;
//    }
//    .moveright {
//        top: 50%;
//        right: 0px;
//        background-position: 0px 0px;
//    }
//    .movedown {
//        bottom: 0px;
//        left: 50%;
//        background-position: 32px 32px;
//    }

// === TPL FILE MUST CONTAIN (replace "test" with namePrefix) ===
//    <!-- BEGIN ScrollerBlock -->
//    <div id="{playerId}_scrollmap" class="scrollerClass">
//      <div style="color:#{playerColor}"> {playerName} </div>
//      <div id="{playerId}_scrollmap_noclick" class="scroller_noclick"> </div>
//      <div class="scroller_surface">
//        <div class="movedown"> </div>
//        <div class="movetop"> </div>
//        <div class="moveleft"> </div>
//        <div class="moveright"> </div>
//      </div>
//      <div id="{playerId}_scrollmap_clickable" class="scroller_clickable"> </div>
//    </div>
//    <!-- END ScrollerBlock -->

class Scroller
{
  // Call with: varName = new Scroller(new ebg.scrollmap(), "bob")
  constructor(ebgScroller, namePrefix, allowZoom = 1)
  {
    // console.log("scroller.js constructor for "+namePrefix);
    this.s = ebgScroller;
    this.prefix = namePrefix
    this.noClickNode = dojo.query("#"+namePrefix+"_scrollmap>.scroller_noclick")[0];
    this.clickableNode = dojo.query("#"+namePrefix+"_scrollmap>.scroller_clickable")[0];

    // this.s.create( $('map_container'),$('map_scrollable'),$('map_surface'),$('map_scrollable_oversurface') );
    this.s.create( dojo.query("#"+namePrefix+"_scrollmap")[0],
                   dojo.query("#"+namePrefix+"_scrollmap>.scroller_noclick")[0],
                   dojo.query("#"+namePrefix+"_scrollmap>.scroller_surface")[0],
                   dojo.query("#"+namePrefix+"_scrollmap>.scroller_clickable")[0] );
    // WARNING: The setupOnScreenArrows() function is broken. Due to a typo, it doesn't enable the down arrow!
    //          I'll set it up here manually instead
    // this.s.setupOnScreenArrows( 150 );
    this.s.scrollDelta = 150;
    dojo.query("#" + namePrefix + "_scrollmap .movetop").connect("onclick", this.s, "onMoveTop").style("cursor", "pointer");
    dojo.query("#" + namePrefix + "_scrollmap .moveleft").connect("onclick", this.s, "onMoveLeft").style("cursor", "pointer");
    dojo.query("#" + namePrefix + "_scrollmap .moveright").connect("onclick", this.s, "onMoveRight").style("cursor", "pointer");
    dojo.query("#" + namePrefix + "_scrollmap .movedown").connect("onclick", this.s, "onMoveDown").style("cursor", "pointer");

    this.zoom = 1.0;
    if (allowZoom)
      dojo.query("#"+namePrefix+"_scrollmap").connect('onwheel', this, 'onWheel');
    
    // in order to use the 2 following events handlers, the following code need to be added as child of {PLAYER_ID}_scrollmap
    //<div id="zoom_buttons"><i class="fa fa-search-plus" style="font-size: 1.7em;"></i><i class="fa fa-search-minus" style="font-size: 1.7em;"></i><i class="fa fa-expand" style="font-size: 1.7em;margin: 2px;"></i></div>
    // css : #zoom_buttons{position: absolute;top: 5px;right: 5px;width: 30px;height: 60px;display: flex;flex-wrap: wrap;justify-content: flex-end;z-index: 3;color: #efefefcf;}
    dojo.query('#'+namePrefix+'_scrollmap > #zoom_buttons > i.fa.fa-search-plus').connect('onclick', this, 'onZoomIn');
    dojo.query('#'+namePrefix+'_scrollmap > #zoom_buttons > i.fa.fa-search-minus').connect('onclick', this, 'onZoomOut');
    dojo.query('#'+namePrefix+'_scrollmap > #zoom_buttons > i.fa.fa-expand').connect('onclick', this, 'onCenter');

  }

  // call with obj.moveIdToPos(this,"htmlId", x,y);
  moveIdToPos(that,htmlId, x, y, clickable=0, duration=500, delay=0)
  {
    // console.log("scroller.movIdToPos("+htmlId+", ("+x+","+y+"), "+clickable+", "+duration+","+delay+")")
    this.setScale(1.0, 0); // Need to reset scale before sliding due to flaw in slide function
    if (clickable)
    {
      this.clickableNode.appendChild( dojo.byId(htmlId) );
      that.slideToObjectPos( htmlId, this.prefix+'_scrollmap_clickable',x,y, duration, delay ).play();
    }
    else
    {
      this.noClickNode.appendChild( dojo.byId(htmlId) );
      that.slideToObjectPos( htmlId, this.prefix+'_scrollmap_noclick',x,y, duration, delay ).play();
    }
    this.setScale();
  }

  // call with
  addHtml(html, clickable = 0)
  {
    if (clickable)
      dojo.place(html, this.prefix+'_scrollmap_clickable');
    else
      dojo.place(html, this.prefix+'_scrollmap_noclick');
  }

  scrollTo(x,y)
  {
    this.s.scrollto(x,y);
  }


  // Zoom functions ---------------------------------------------------------------
  setScale(newScale, override=1)
  {
    // console.log("setScale("+newScale+", "+override+")");
    if (newScale == undefined)
      newScale = this.zoom;
    else if (override)
      this.zoom = newScale; // Caller may override current zoom setting
    // This works for Firefox, but I guess not for Chrome :(
    // this.noClickNode.style.scale = newScale;
    // this.clickableNode.style.scale = newScale;

    this.noClickNode.style.transform = "scale("+newScale+")";
    this.clickableNode.style.transform = "scale("+newScale+")";
  }
  onWheel(evt)
  {
    if (evt.ctrlKey) return; // either a pinch event or a whole-page zoom event (hitting the ctrl key while scrolling)
    // if (!evt.wheelDeltaY || Math.abs(evt.wheelDeltaY) !== 120) return; // mouse wheels have exactly 120 for delta
    if (evt.deltaY < 0) this.onZoomIn(evt);
    if (evt.deltaY > 0) this.onZoomOut(evt);
  }
  onZoomIn(evt)
  {
    evt.preventDefault()
    this.zoom += 0.1
    if (this.zoom > 1.5) this.zoom = 1.5;
    this.setScale();
  }
  onZoomOut(evt)
  {
    evt.preventDefault()
    this.zoom -= 0.1
    if (this.zoom < 0.2) this.zoom = 0.2;
    this.setScale();
  }


  // Centering function ---------------------------------------------------------------
  // in order to use this function, the data-x and data-y attributes must be set on the html elements on the no-click surface
  onCenter(evt)
  {
    //evt.preventDefault()
    // UPDATE POSITION
    let containerChilds = document.getElementById(this.prefix+"_scrollmap_noclick").children;
    let x_vals = [];
    let yvals = [];
    for ( let counter=0; counter < containerChilds.length; counter++)
    {
      let value_x = toint(containerChilds[counter].getAttribute("data-x"));
      let value_y = toint(containerChilds[counter].getAttribute("data-y"));
      x_vals.push(value_x);
      yvals.push(value_y);
    }
    let max_x = Math.max.apply(Math, x_vals) *2 + 50;
    let min_x = Math.min.apply(Math, x_vals) *2;
    let max_y = Math.max.apply(Math, yvals) *2 +100;
    let min_y = Math.min.apply(Math, yvals) *2;
    debug("max_x="+max_x+" max_y="+max_y+" min_x="+min_x+" min_y="+min_y);
    let center_x = (max_x + min_x)/2;
    let center_y = (max_y + min_y)/2;
    debug("center_x="+center_x+" center_y="+center_y);
    if (max_y % 25 != 0 || min_y% 25 != 0) {
      center_y -= 32
      debug('correct center_y to '+center_y);
    }
    if (isNaN(center_x) || isNaN(center_x)) {
      debug('center_x or center_y is NaN');
      this.scrollTo(-50, -50);
      return;
    } else {
      this.scrollTo(-center_x, -center_y);
    }
    // UPDATE ZOOM
    // zoom 1 : contains 4 on Y  and 11 on X
    // zoom 0.9 : contains 4 on Y and 12 on X
    // zoom 0.8 : contains 5 on Y and 14 on X
    // zoom 0.7 : contains 5 on Y and 16 on X
    // zoom 0.6 : contains 6 on Y and 1? on X
    // zoom 0.5 : contains 8 on Y and 1? on X
    let ScrollerContainer = document.getElementById(this.prefix+'_scrollmap');
    let containerWidth = ScrollerContainer.offsetWidth;
    let containerHeight = ScrollerContainer.offsetHeight;
    let n_y = (max_y - min_y) /100;
    let n_x = (max_x - min_x) / 50;
    debug("n_x="+n_x+" n_y="+n_y);
    let zoom_x = 1;
    let zoom_y = 1;
    if (n_y <= 4) {
      zoom_y = 1
    } else if (4 < n_y && n_y <= 5) {
      zoom_y = 0.8
    } else if (5 < n_y && n_y <= 6) {
      zoom_y = 0.6
    } else if (6 < n_y && n_y <= 8) {
      zoom_y = 0.5
    } else if (n_y > 8) {
      zoom_y = 0.4
    }
    if (n_x <= 11) {
      zoom_x = 1
    } else if (11 < n_x && n_x <= 12) {
      zoom_x = 0.9
    } else if (12 < n_x && n_x <= 14) {
      zoom_x = 0.8
    } else if (14 < n_x && n_x <= 16) {
      zoom_x = 0.7
    } else if (n_x > 16) {
      zoom_x = 0.6
    }
    debug("zoom_x="+zoom_x+" zoom_y="+zoom_y);
    let zoom = Math.min(zoom_x, zoom_y);
    debug("zoom="+zoom);
    this.setScale(zoom);
  }
}

