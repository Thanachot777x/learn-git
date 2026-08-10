import qrcode

# link เข้าสู่ระบบ (ผ่าน ngrok)
data = "https://pyramid-bonus-subplot.ngrok-free.dev/auth/login.php"

img = qrcode.make(data)
img.save("myqrcode.png")

print("สร้าง QR Code เสร็จแล้ว!")