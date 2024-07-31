// colorUtils.js

/**
 * Get the inverse of a color and optionally convert it to black or white.
 *   via https://stackoverflow.com/a/35970186/2566038
 *
 * @param hex A 3- or 6-digit hex color.
 * @param bw A truthy value to determine whether to return the inverted color or
 *   black or white.
 * @returns {string} A hex color, either the inverse of the input or black/white.
 */
export function invertColor(hex, bw) {
  if (hex.indexOf('#') === 0) {
    hex = hex.slice(1);
  }
  // convert 3-digit hex to 6-digits.
  if (hex.length === 3) {
    hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
  }
  if (hex.length !== 6) {
    throw new Error('Invalid HEX color.');
  }
  var r = parseInt(hex.slice(0, 2), 16),
    g = parseInt(hex.slice(2, 4), 16),
    b = parseInt(hex.slice(4, 6), 16);
  if (bw) {
    // https://stackoverflow.com/a/3943023/112731
    return (r * 0.299 + g * 0.587 + b * 0.114) > 149
      ? '#000000'
      : '#FFFFFF';
  }
  // invert color components
  r = (255 - r).toString(16);
  g = (255 - g).toString(16);
  b = (255 - b).toString(16);
  // pad each with zeros and return
  return "#" + padZero(r) + padZero(g) + padZero(b);
}

/**
 * Transforms a 3- or 6- digit hex and a transparency value into RGBA.
 * Adapted from https://css-tricks.com/converting-color-spaces-in-javascript/#aa-hex-rrggbbaa-to-rgba
 *
 * @param h A 3- or 6- digit hex code.
 * @param a The alpha/transparency value, as a decimal from 0 to 1.
 * @returns {string} A converted RGBA color code.
 */
export function hexToRGBA(h, a = 1) {
  let r = 0, g = 0, b = 0;

  if (h.length == 4) {
    r = "0x" + h[1] + h[1];
    g = "0x" + h[2] + h[2];
    b = "0x" + h[3] + h[3];

  } else if (h.length == 7) {
    r = "0x" + h[1] + h[2];
    g = "0x" + h[3] + h[4];
    b = "0x" + h[5] + h[6];
  }

  return "rgba(" + +r + "," + +g + "," + +b + "," + a + ")";
}

/**
 * Adds transparency to an RGB color.
 *
 * @param rgb A RGB color string.
 * @param a The alpha/transparency value, as a decimal from 0 to 1.
 * @returns {string} An RGBA color string.
 */
export function rgbToRGBA( rgb, a = 1) {
  let sep = rgb.indexOf(",") > -1 ? "," : " ";
  rgb = rgb.substr(4).split(")")[0].split(sep);

  return "rgba(" + rgb[0] + "," + rgb[1] + "," + rgb[2] + "," + a + ")";
}
