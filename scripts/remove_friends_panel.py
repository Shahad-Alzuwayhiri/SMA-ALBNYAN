import os
p = r'c:\Users\Admin\Desktop\ContractSama\templates\_friends_panel.html'
print('exists?', os.path.exists(p))
if os.path.exists(p):
    print('size', os.path.getsize(p))
    os.remove(p)
    print('removed')
else:
    print('not found')
