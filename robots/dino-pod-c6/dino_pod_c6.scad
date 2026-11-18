// Dino Pod C6: an original desk-buddy housing for the Waveshare ESP32-C6-LCD-1.47.
// Two printed parts: BODY (front shell with the screen window, spikes, feet) and LID (back cover with tail).
// Render one part at a time:  openscad -D 'part="body"' -o body.stl dino_pod_c6.scad   (or "lid", or "both" for a preview)
//
// Parts inside (sizes from the sources noted next to each):
//   ESP32-C6-LCD-1.47  PCB 20.32 x 36.37, total depth 7.6, LCD module 19.39 x 36.28,
//                      active area 17.39 x 32.35 (1.0 from LCM top/sides)       - Waveshare official dimension drawing
//   LiPo 602030        6 x 20 x 30 (cell code = thickness x width x length)      - pocket adds room for the protection PCB and wires
//   GY-521 (MPU6050)   ~21 x 16 x 3, sellers vary by ~1 mm                       - pocket sized 22.5 x 17.5
//   TP4056 Type-C      25 to 29 x 17 to 17.3 x 4.3, sellers vary                 - pocket sized 30 x 18
// Fasteners: 2 x M2 x 8 self-tapping screws hold the lid.

part = "both";          // "body" | "lid" | "both"
$fn = 48;

// ---------- overall shell ----------
W = 42;        // width  (x)
D = 36;        // depth  (y), front face at -D/2
H = 58;        // height (z), floor at z = 0
R = 10;        // corner radius of the pod
T = 2.0;       // wall thickness
CUT = 11;      // y where the lid starts (back part)

// ---------- board placement ----------
PCB_W = 20.32; PCB_H = 36.37; BOARD_D = 7.6;
LCM_W = 19.39; LCM_H = 36.28;
AA_W = 17.39;  AA_H = 32.35; AA_TOP = 1.0;   // active area inset from LCM top
FIT = 0.4;                                   // print clearance around parts
BOARD_Z = 5.0;                               // board bottom edge (leaves room for the USB-C plug hole in the floor)
FRONT_IN = -D/2 + T;                         // inner face of the front wall

module pod(w, d, h, r) {
  // rounded box, flat bottom
  translate([0, 0, 0]) intersection() {
    minkowski() {
      translate([-(w/2 - r), -(d/2 - r), r]) cube([w - 2*r, d - 2*r, h - 2*r]);
      sphere(r);
    }
    translate([-w, -d, 0]) cube([2*w, 2*d, h]);   // flatten the bottom so it stands
  }
}

module shell() {
  difference() {
    pod(W, D, H, R);
    translate([0, 0, T]) pod(W - 2*T, D - 2*T, H - 2*T, R - T);
  }
}

// ---------- dino details ----------
module spike(s) {
  // rounded triangular plate standing on the ridge
  hull() {
    translate([0, -s*0.55, 0]) sphere(1.2);
    translate([0,  s*0.55, 0]) sphere(1.2);
    translate([0, 0, s]) sphere(1.0);
  }
}
module spikes() {
  // ridge from the top of the head down the back, kept on the body part (y < CUT)
  pts = [[-9, H - 0.6, 6.0], [-2, H - 0.6, 8.0], [5, H - 1.2, 7.0]];
  for (p = pts) translate([0, p[0], p[1] - 1.2]) rotate([-12, 0, 0]) spike(p[2]);
}
module feet() {
  for (sx = [-1, 1]) translate([sx * 11, -D/2 + 7, 0])
    hull() { translate([0, -3, 2]) scale([1.25, 1.6, 0.7]) sphere(4); translate([0, 3, 0]) cylinder(r = 4, h = 1); }
}
module tail() {
  // short tail curling out of the back, on the lid
  hull() {
    translate([0, D/2 - 3, 9]) sphere(6);
    translate([0, D/2 + 9, 4]) sphere(2.4);
  }
}
module arms() {
  for (sx = [-1, 1]) translate([sx * (W/2 - 0.5), -4, 24]) rotate([0, sx * 25, 0]) scale([0.7, 1, 1.6]) sphere(3.2);
}

// ---------- screen window and board pocket ----------
lcm_top_z  = BOARD_Z + PCB_H;          // LCM top aligned with PCB top (drawing)
aa_top_z   = lcm_top_z - AA_TOP;
aa_mid_z   = aa_top_z - AA_H/2;

module window_cut() {
  // through-window for the active area, chamfered outwards
  hull() {
    translate([-(AA_W/2 + 0.3), FRONT_IN - 0.01, aa_mid_z - AA_H/2 - 0.3]) cube([AA_W + 0.6, 0.01, AA_H + 0.6]);
    translate([-(AA_W/2 + 1.4), -D/2 - 0.5,     aa_mid_z - AA_H/2 - 1.4]) cube([AA_W + 2.8, 0.01, AA_H + 2.8]);
  }
}

module board_cradle() {
  // guide walls around the board, 4 mm deep, with notches for the side buttons and an open top for wires
  wall = 1.4; depth = 4;
  ix = PCB_W/2 + FIT; iz0 = BOARD_Z - FIT; iz1 = BOARD_Z + PCB_H + FIT;
  difference() {
    translate([-(ix + wall), FRONT_IN, iz0 - wall]) cube([2*(ix + wall), depth, (iz1 - iz0) + wall]);
    translate([-ix, FRONT_IN - 0.1, iz0]) cube([2*ix, depth + 0.2, iz1 - iz0 + 1]);
    // BOOT / RESET buttons stick out of both long sides near the bottom (drawing: ~7 mm up)
    for (sx = [-1, 1]) translate([sx * (ix + wall/2) - 2, FRONT_IN - 0.1, BOARD_Z + 3]) cube([4, depth + 0.2, 8]);
  }
}

module usb_floor_cut() {
  // the board's own USB-C (for flashing) points down: hole straight through the floor under it
  translate([-5.5, FRONT_IN + 0.6, -1]) cube([11, 4.2, BOARD_Z + 1.2]);
}

// ---------- battery tray (behind the board) ----------
BAT = [21.5, 7.0, 36];           // x, y, z pocket: 20 x 6 x 30 cell + protection PCB and leads
bat_y0 = FRONT_IN + BOARD_D + 1.2;
module battery_tray() {
  wall = 1.2;
  difference() {
    translate([-(BAT[0]/2 + wall), bat_y0 - wall, T]) cube([BAT[0] + 2*wall, BAT[1] + 2*wall, 26]);
    translate([-BAT[0]/2, bat_y0, T + 0.01]) cube([BAT[0], BAT[1], 40]);
    translate([-6, bat_y0 - wall - 0.1, T + 6]) cube([12, wall + 0.2, 30]);   // window towards the board for wires
  }
}

// ---------- screw bosses (body) and holes (lid) ----------
boss_pts = [[-13, 32], [13, 32]];
module bosses() {
  for (p = boss_pts) translate([p[0], CUT - 7, p[1]]) difference() {
    hull() { cylinder(d = 6.4, h = 1, center = true); translate([(p[0] > 0 ? 1 : -1) * 3, 0, 0]) cube([1, 6.4, 6.4], center = true); }
    rotate([-90, 0, 0]) cylinder(d = 1.7, h = 20, center = true);   // M2 self-tapping pilot
  }
}
module boss_blocks() {
  // each boss is a block that reaches out to the side wall, with a 45° underside so it prints without supports
  for (p = boss_pts) {
    s = p[0] > 0 ? 1 : -1;
    difference() {
      hull() {
        translate([p[0] - 3.2, CUT - 7, p[1] - 3.2]) cube([6.4, 7, 6.4]);
        translate([s > 0 ? W/2 - T : -(W/2 - T) - 1, CUT - 7, p[1] - 3.2 - 7]) cube([1, 7, 13.6]);
      }
      translate([p[0], CUT, p[1]]) rotate([-90, 0, 0]) cylinder(d = 1.7, h = 20, center = true);
    }
  }
}
// decorations are solid on the outside only: anything that pokes into the cavity is cut away
module deco() {
  difference() {
    union() { spikes(); feet(); arms(); }
    translate([0, 0, T]) pod(W - 2*T, D - 2*T, H - 2*T, R - T);
  }
}

// ---------- lid internals ----------
LID_IN = D/2 - T;                // inner face of the back wall
module module_pockets() {
  // GY-521 high up, TP4056 low with its USB-C pointing down through the lid's lower curve
  translate([0, LID_IN, 36]) pocket([22.5, 17.5], 2.5);
  translate([0, LID_IN, 6]) pocket([18, 30], 2.5, true);
}
module pocket(sz, h, rot = false) {
  // shallow frame glued to the inside of the back wall
  wall = 1.2;
  w = sz[0]; ht = sz[1];
  difference() {
    translate([-(w/2 + wall), -h, 0]) cube([w + 2*wall, h, ht + wall]);
    translate([-w/2, -h - 0.1, (rot ? -0.1 : wall)]) cube([w, h + 0.2, ht + (rot ? 0.2 : 0)]);
  }
}
module tp4056_port_cut() {
  // USB-C receptacle ~9 x 3.3: opening 10 x 4.4 through the bottom-back of the lid
  translate([-5, LID_IN - 4.6, -1]) cube([10, 4.4, 8]);
}

// ---------- assemble ----------
module body() {
  difference() {
    union() {
      intersection() { shell(); translate([-W, -D, -1]) cube([2*W, D + CUT, H + 2]); }
      intersection() { union() { board_cradle(); battery_tray(); boss_blocks(); }
                       translate([0, 0, T]) pod(W - 2*T + 0.01, D - 2*T + 0.01, H - 2*T, R - T); }
      deco();
    }
    window_cut();
    usb_floor_cut();
    for (p = boss_pts) translate([p[0], CUT, p[1]]) rotate([-90, 0, 0]) cylinder(d = 1.7, h = 30, center = true);
  }
}

module lid() {
  difference() {
    union() {
      intersection() { shell(); translate([-W, CUT, -1]) cube([2*W, D, H + 2]); }
      // locating lip that slides into the body
      intersection() {
        difference() {
          translate([0, 0, T]) pod(W - 2*T - 0.2, D - 2*T - 0.2, H - 2*T - 0.2, R - T - 0.1);
          translate([0, 0, T + 1.4]) pod(W - 2*T - 2.8, D - 2*T - 2.8, H - 2*T - 2.8, R - T - 1.4);
        }
        translate([-W, CUT - 2, -1]) cube([2*W, 2.6, H + 2]);
      }
      // a 1.5 mm ring just behind the cut joins the lip to the lid wall (the lip itself keeps 0.1 mm clearance)
      intersection() {
        difference() { pod(W, D, H, R); translate([0, 0, T + 1.4]) pod(W - 2*T - 2.8, D - 2*T - 2.8, H - 2*T - 2.8, R - T - 1.4); }
        translate([-W, CUT, -1]) cube([2*W, 1.5, H + 2]);
      }
      intersection() { module_pockets(); translate([0, 0, T]) pod(W - 2*T, D - 2*T, H - 2*T, R - T); }
      difference() { tail(); translate([0, 0, T]) pod(W - 2*T, D - 2*T, H - 2*T, R - T); }
    }
    tp4056_port_cut();
    for (p = boss_pts) translate([p[0], CUT + 4, p[1]]) rotate([-90, 0, 0]) {
      cylinder(d = 2.3, h = 30, center = true);                   // M2 clearance
      translate([0, 0, D/2 - CUT - 4 - 1.6]) cylinder(d = 4.2, h = 10); // head recess from the outside
    }
  }
}

// ---------- fit check: real part sizes as dummies, section cut through the middle ----------
module dummies() {
  color("royalblue") translate([-PCB_W/2, FRONT_IN, BOARD_Z]) cube([PCB_W, BOARD_D, PCB_H]);           // board + LCD
  color("red")       translate([-10, bat_y0 + 0.5, T + 0.5]) cube([20, 6, 30]);                       // LiPo 602030
  color("orange")    translate([-10.5, LID_IN - 2.5, 37.2]) cube([21, 1.6, 16]);                      // GY-521
  color("purple")    translate([-8.65, LID_IN - 2.5, 6.0]) cube([17.3, 1.6, 29]);                     // TP4056 (largest listed)
  color("silver")    translate([-4.5, LID_IN - 4.4, 2.4]) cube([9, 3.3, 7.5]);                         // its USB-C receptacle
}

if (part == "fit") {
  dummies();
  difference() { union() { body(); lid(); } translate([0, -50, -10]) cube([100, 100, 100]); }
}
else if (part == "shot") color("#7bc67e") body();
else if (part == "shotlid") color("#7bc67e") lid();
else if (part == "body") body();
else if (part == "lid") translate([0, 0, 0]) lid();
else { color("#7bc67e") body(); color("#5aa65d") translate([0, 30, 0]) lid(); }
