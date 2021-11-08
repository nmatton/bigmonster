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
}

