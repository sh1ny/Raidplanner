(function($) {
    
	  var jClock = window.jClock = function(clock, canvas, options) {
	    
	    var ctx, img;

	    // Canvas isn't supported, abort
	    if(!(ctx = canvas.getContext('2d'))) return;
	    
	    options = $.extend(true, {}, jClock.defaults, options);
	    img = new Image();
	    img.src = clock;
	    
	    // Need to wait until after the image is loaded
	    img.onload = function() {
	      tick();
	      setInterval(tick, 1000);
	    };
	    
	    // The ticker, draws the clock upon each tick
	    function tick() {
	      var now = new Date(),
	          sec = now.getSeconds(),
	          min = now.getMinutes(),
	          hour = now.getHours();
	      if(hour > 12) hour = hour % 12;
	      
	      // do the clock
	      drawClock();
	      
	      // do the second hand
	      if(options.secondHand === true) drawHand(sec * Math.PI/30, options.second);
	      
	      // do the minute hand
	      drawHand((min + sec/60) * Math.PI/30, options.minute);
	      
	      // do the hour hand
	      drawHand((hour + sec/3600 + min/60) * Math.PI/6, options.hour);
	    }
	    
	    function drawClock() {
	      ctx.clearRect(0, 0, options.height, options.width);
	      ctx.drawImage(img, 0, 0, options.width, options.height);
	      ctx.save();
	    }
	    
	    function drawHand(radians, opts) {
	      radians -= 90 * Math.PI/180; // fix orientation
	      
	      ctx.save();
	      ctx.beginPath();
	      ctx.translate(options.height/2, options.width/2);
	      
	      // Set hand styles
	      ctx.strokeStyle = opts.color;
	      ctx.lineWidth = opts.width;
	      ctx.globalAlpha = opts.alpha;
	      if (options.shadow === true) {
	        ctx.shadowOffsetX = 2;
	        ctx.shadowOffsetY = 2;
	        ctx.shadowBlur = 1;
	        ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
	      }
	                
	      ctx.rotate(radians);
	      ctx.moveTo(opts.start, 0);
	      ctx.lineTo(opts.end, 0);
	      ctx.stroke();
	      ctx.restore();
	    }

	  };
	  
	  // Default options
	  jClock.defaults = {   
	    height: 125,
	    width: 125,
	    secondHand: true,
	    shadow: true,
	    second: {
	      color: '#f00',
	      width: 2,
	      start: -10,
	      end: 35,
	      alpha: 1
	    },
	    minute: {
	      color: '#fff',
	      width: 3,
	      start: -7,
	      end: 30,
	      alpha: 1
	    },
	    hour: {
	      color: '#fff',
	      width: 4,
	      start: -7,
	      end: 20,
	      alpha: 1
	    }     
	  };
	  
	})(jQuery);