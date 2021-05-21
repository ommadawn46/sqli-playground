import sys
import binascii

input_hex = sys.argv[1]
if input_hex.startswith("0x"):
    input_hex = input_hex[2:]

input_bytes = binascii.unhexlify(input_hex)
input_str = input_bytes.decode()

print(input_str)
