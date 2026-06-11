package ua

import (
	"strings"
)

type Info struct {
	Device  string
	OS      string
	Browser string
}

func Parse(userAgent string) Info {
	ua := strings.ToLower(userAgent)
	info := Info{
		Device:  "desktop",
		OS:      "unknown",
		Browser: "unknown",
	}

	switch {
	case strings.Contains(ua, "iphone"), strings.Contains(ua, "ipod"):
		info.Device = "mobile"
		info.OS = "iOS"
	case strings.Contains(ua, "ipad"):
		info.Device = "tablet"
		info.OS = "iOS"
	case strings.Contains(ua, "android"):
		if strings.Contains(ua, "mobile") {
			info.Device = "mobile"
		} else {
			info.Device = "tablet"
		}
		info.OS = "Android"
	case strings.Contains(ua, "windows phone"):
		info.Device = "mobile"
		info.OS = "Windows Phone"
	case strings.Contains(ua, "mac os"), strings.Contains(ua, "macintosh"):
		info.OS = "macOS"
	case strings.Contains(ua, "windows"):
		info.OS = "Windows"
	case strings.Contains(ua, "linux"):
		info.OS = "Linux"
	case strings.Contains(ua, "cros"):
		info.OS = "ChromeOS"
	}

	switch {
	case strings.Contains(ua, "edg/"), strings.Contains(ua, "edge/"):
		info.Browser = "Edge"
	case strings.Contains(ua, "opr/"), strings.Contains(ua, "opera"):
		info.Browser = "Opera"
	case strings.Contains(ua, "chrome/"), strings.Contains(ua, "crios/"):
		info.Browser = "Chrome"
	case strings.Contains(ua, "firefox/"), strings.Contains(ua, "fxios/"):
		info.Browser = "Firefox"
	case strings.Contains(ua, "safari/") && !strings.Contains(ua, "chrome/"):
		info.Browser = "Safari"
	case strings.Contains(ua, "msie"), strings.Contains(ua, "trident/"):
		info.Browser = "IE"
	}

	return info
}
