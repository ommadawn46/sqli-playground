import sys
import binascii

input_str = sys.argv[1]

input_bytes = binascii.hexlify(input_str.encode())
input_hex = input_bytes.decode()

print(input_hex)
