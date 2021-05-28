import sys

PAGE_SIZE = 2048

bin_file = sys.argv[1]
loid = sys.argv[2]

with open(bin_file, "rb") as f:
    bin_data = f.read()

for i in range(0, len(bin_data), PAGE_SIZE):
    pageno = i // PAGE_SIZE
    hex_page = "".join(map(lambda x: "%02x" % x, bin_data[i : i + PAGE_SIZE]))

    if pageno == 0:
        print(
            f"'; UPDATE PG_LARGEOBJECT SET data=decode('{hex_page}','hex') WHERE loid={loid} AND pageno={pageno};--+"
        )
    else:
        print(
            f"'; INSERT INTO PG_LARGEOBJECT (loid,pageno,data) VALUES ({loid},{pageno},decode('{hex_page}','hex'));--+"
        )
