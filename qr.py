import qrcode

# link เข้า
data = "http://192.168.1.12/it_support/auth/login.php"

img = qrcode.make(data)
img.save("myqrcode.png")

print("สร้าง QR Code เสร็จแล้ว!")