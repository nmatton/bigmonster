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
  }

  // call with obj.moveIdToPos(this,"htmlId", x,y);
  moveIdToPos(that,htmlId, x, y, clickable=0, duration=500, delay=0, onEnd=null)
  {
    //clickable = true;
    // console.log("scroller.movIdToPos("+htmlId+", ("+x+","+y+"), "+clickable+", "+duration+","+delay+")")
    var anim;
    if (clickable)
    {
      this.clickableNode.appendChild( dojo.byId(htmlId) );
      anim = that.slideToObjectRelPos( htmlId, this.prefix+'_scrollmap_clickable',x,y, duration, delay );

    }
    else
    {
      this.noClickNode.appendChild( dojo.byId(htmlId) );
      anim = that.slideToObjectRelPos( htmlId, this.prefix+'_scrollmap_noclick',x,y, duration, delay );
    }
    if (onEnd){
      // dojo.connect(anim, 'onEnd', onEnd);
    }
    anim.play();
  }

  // call with
  addHtml(html, clickable = 0)
  {
    //clickable = true;
    if (clickable)
      dojo.place(html, this.prefix+'_scrollmap_clickable');
    else
      dojo.place(html, this.prefix+'_scrollmap_noclick');
  }

  scrollTo(x,y)
  {
    this.s.scrollto(x,y);
  }
  onCenter()
  {
    this.s.scrollToCenter();
  }

}

