<?php

	/*
	 * @Author: Florian Fries <mail@flolefries.com>
	 * @Date: 07/2014
	 * @Description: FlappyBird-like game to run on the edge laser 
	 * 
	*/

	include('EdgeLaser.ns.php');

	use EdgeLaser\LaserGame;
	use EdgeLaser\LaserColor;
	use EdgeLaser\LaserFont;
	use EdgeLaser\XboxKey;

	$game = new LaserGame('Flappy Fest');

	$game->setResolution(500)->setDefaultColor(LaserColor::LIME)->setFramerate(20);

	$font_lcd = new LaserFont('fonts/lcd.elfc');
	
	$gamestarted = 60;
	$currenttime = 0;
	$ytext = 0;
	$bounce = 60;
	$bouncestep = 12;
	$fallstep = 10;
	$xplayer1 = 150;
	$xplayer2 = 150;
	$yplayer1 = 250;
	$yplayer2 = 250;
	$spacing = 60;
	$player1size = 75;
	$player2size = 75;
	$p1color = LaserColor::CYAN;
	$p2color = LaserColor::GREEN;
	$wallcolor = LaserColor::WHITE;
	$p1bouncing = 0;
	$p2bouncing = 0;	
	$breaksize = 180;
	$wallstep = 6;
	$keydownp1 = false;
	$keydownp2 = false;
	$slowsfallp1 = 9;
	$slowsfallp2 = 9;
	$scorep1 = 0;
	$scorep2 = 0;
	$p1dead = false;
	$p2dead = false;
	$nwall = 0;
	$wallreached = array();
	
	$wall1 = array("ywall" => 480, "ywidth" => 20, "ybreak" => rand(40,500-40-$breaksize));
	$wall2 = array("ywall" => 300, "ywidth" => 20, "ybreak" => rand(40,500-40-$breaksize));	
	
	while(true)
	{
		$commands = $game->receiveServerCommands();

		if(!$game->isStopped())
		{
			$game->newFrame(); //Used for framerate control
			$game->requireKinect();

			if($currenttime < $starttime/2)
			{
				$yline = (200 * $currenttime) / 100;
				$font_lcd->render($game, 'FLAPPY', 90, $yline, LaserColor::WHITE, 4);
				$currenttime++;
			}
			elseif($currenttime < $starttime)
			{
				$yline = 500 - ((200 * $currenttime) / 100);
				$font_lcd->render($game, 'FEST', 150, $yline, LaserColor::WHITE, 4);
				$currenttime++;
			}
			elseif($currenttime < $starttime + 60)
			{
				$txtcd = ($currenttime - $starttime < 20) ? "3" : ((($currenttime - $starttime) < 40) ? "2" : "1");
				$font_lcd->render($game, $txtcd, 200, 200, LaserColor::WHITE, 8);
				$currenttime++;
			}
			else
			{			
				foreach(XboxKey::getKeys() as $key) //Pressed keys
				{
					if($key == XboxKey::P1_A && !$p1dead && !$keydownp1 && $p1bouncing == 0)	$p1bouncing = $bouncestep;
					//if($key == XboxKey::P1_ARROW_LEFT && !$p1dead)	$xplayer1 -= 5;
					if($key == XboxKey::P1_ARROW_RIGHT && !$p1dead)	$xplayer1 += 5;
					if($key == XboxKey::P2_A && !$p2dead && !$keydownp2 && $p2bouncing == 0)	$p2bouncing = $bouncestep;
					//if($key == XboxKey::P2_ARROW_LEFT && !$p2dead)	$xplayer2 -= 5;
					if($key == XboxKey::P2_ARROW_RIGHT && !$p2dead)	$xplayer2 += 5;
				}
				
				$keydownp1 = in_array(XboxKey::P1_A, XboxKey::getKeys());
				$keydownp2 = in_array(XboxKey::P2_A, XboxKey::getKeys());
				
				// move the walls
				if(!($p1dead && $p2dead))
				{
					if($wall1["ywall"] > $wall1["ywidth"])
					{
						$wall1["ywall"] -= $wallstep;
						// manage collisions and scores for player 1
						if($wall1["ywall"] < $xplayer1 + ($player1size/2))
						{
							if($yplayer1 - ($player1size/2) > $wall1["ybreak"] && $yplayer1 + ($player1size/2) < $wall1["ybreak"] + $breaksize)
							{
								if(!isset($wallreached[$nwall]["p1"]))
								{
									$scorep1++;
									$wallreached[$nwall]["p1"] = true;
									$player1size += 4;
								}
							}
							elseif($wall1["ywall"] + $wall1["ywidth"] > ($xplayer1 - ($player1size/2)))
							{
								$p1dead = true;
							}
						}
						// manage collisions and scores for player 2
						if($wall1["ywall"] < $xplayer2 + ($player2size/2))
						{
							if($yplayer2 - ($player2size/2) > $wall1["ybreak"] && $yplayer2 + ($player2size/2) < $wall1["ybreak"] + $breaksize)
							{
								if(!isset($wallreached[$nwall]["p2"]))
								{
									$scorep2++;
									$wallreached[$nwall]["p2"] = true;
									$player2size += 4;
								}
							}
							elseif($wall1["ywall"] + $wall1["ywidth"] > ($xplayer2 - ($player2size/2)))
							{
								$p2dead = true;
							}
						}
					}
					else
					{
						$wall1 = array("ywall" => 480, "ywidth" => 20, "ybreak" => rand(40,500-40-$breaksize));
						$nwall++;
					}
				}
				else
				{
					// display scores
					$scorep1size = ($scorep1 >= $scorep2 ? 5 : 3);
					$scorep2size = ($scorep2 >= $scorep1 ? 5 : 3);
					$font_lcd->render($game, $scorep1, 200, $wall1["ybreak"] + round($breaksize*0.4)+(($scorep1size % 4)*5), $p1color, $scorep1size);
					$font_lcd->render($game, $scorep2, 300, $wall1["ybreak"] + round($breaksize*0.4)+(($scorep2size % 4)*5), $p2color, $scorep2size);
				}
				
				// bouncing player 1
				if($p1bouncing > 0)
				{
					if($p1bouncing < $bounce)
					{
						$p1bouncing += $bouncestep;
						$yplayer1 -= $bouncestep;
					}
					else
					{
						// stop the bouncing
						$p1bouncing = 0;
						$slowsfallp1 = 9;
					}
				}
				else
				{
					if($yplayer1 < 500) {
						// freefall
						if($slowsfallp1 < 10 && $slowsfallp1 > 0)
						{
							$yplayer1 += ($fallstep - $slowsfallp1);
							$slowsfallp1--;
						}
						else	$yplayer1 += $fallstep;
					}
				}
				
				// bouncing player 2
				if($p2bouncing > 0)
				{
					if($p2bouncing < $bounce)
					{
						$p2bouncing += $bouncestep;
						$yplayer2 -= $bouncestep;
					}
					else
					{
						// stop the bouncing
						$p2bouncing = 0;
						$slowsfallp2 = 9;
					}
				}
				else
				{
					if($yplayer2 < 500) {
						// freefall
						if($slowsfallp2 < 10 && $slowsfallp2 > 0)
						{
							$yplayer2 += ($fallstep - $slowsfallp2);
							$slowsfallp2--;
						}
						else	$yplayer2 += $fallstep;
					}
				}
				
				// draw wall 1
				$game
					->addRectangle($wall1["ywall"], 0, $wall1["ywall"]+$wall1["ywidth"], $wall1["ybreak"], $wallcolor)
					->addRectangle($wall1["ywall"], $wall1["ybreak"]+$breaksize, $wall1["ywall"]+$wall1["ywidth"], 500, $wallcolor);
				
				// draw player 1
				$game
					->addCircle($xplayer1, $yplayer1, $player1size, $p1color);
				if(!$p1dead)
				{
					$game
						->addCircle($xplayer1-round($player1size/8.5), $yplayer1-round($player1size/6), round($player1size/4), $p1color) 
						->addCircle($xplayer1+round($player1size/8.5), $yplayer1-round($player1size/6), round($player1size/4), $p1color)
						->addLine($xplayer1-round($player1size/4), $yplayer1+round($player1size/5), $xplayer1, $yplayer1+round($player1size/3), $p1color)
						->addLine($xplayer1, $yplayer1+round($player1size/3), $xplayer1+round($player1size/4), $yplayer1+round($player1size/5), $p1color);
				}
					
				// draw player 2
				$game
					->addCircle($xplayer2, $yplayer2, $player2size, $p2color) ;
				if(!$p2dead)
				{
					$game
						->addCircle($xplayer2-round($player2size/8.5), $yplayer2-round($player2size/6), round($player2size/4), $p2color) 
						->addCircle($xplayer2+round($player2size/8.5), $yplayer2-round($player2size/6), round($player2size/4), $p2color)
						->addLine($xplayer2-round($player2size/4), $yplayer2+round($player2size/5), $xplayer2, $yplayer2+round($player2size/3), $p2color)
						->addLine($xplayer2, $yplayer2+round($player2size/3), $xplayer2+round($player2size/4), $yplayer2+round($player2size/5), $p2color);
				}
			}
				
			$game->refresh();
							
			$game->endFrame(); //Used for framerate control			
		}
		else
		{		
			$starttime = 60;
			$currenttime = 0;			
			$bounce = 60;
			$bouncestep = 12;
			$fallstep = 10;
			$xplayer1 = 150;
			$xplayer2 = 150;
			$yplayer1 = 250;
			$yplayer2 = 250;
			$spacing = 60;
			$player1size = 75;
			$player2size = 75;
			$p1color = LaserColor::CYAN;
			$p2color = LaserColor::GREEN;
			$wallcolor = LaserColor::WHITE;
			$p1bouncing = 0;
			$p2bouncing = 0;	
			$breaksize = 180;
			$wallstep = 6;
			$keydownp1 = false;
			$keydownp2 = false;
			$slowsfallp1 = 9;
			$slowsfallp2 = 9;
			$scorep1 = 0;
			$scorep2 = 0;
			$p1dead = false;
			$p2dead = false;
			$nwall = 0;
			$wallreached = array();
			
			$wall1 = array("ywall" => 480, "ywidth" => 20, "ybreak" => rand(40,500-40-$breaksize));
			$wall2 = array("ywall" => 300, "ywidth" => 20, "ybreak" => rand(40,500-40-$breaksize));
			
			sleep(1);
		}
	}

?>
