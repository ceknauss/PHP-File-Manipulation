<?php

/***** PHP Class - fileManipulation ********************************************
 * Created:      2019-04-03
 * Modified:     2019-04-09
 * Contributors: Corwin Knauss, Tim Wolfe
 * Description:  Generic Class to handle any file manipulation as required.
 ******************************************************************************/

class fileManipulation
{
	/***** Member Variables *****/
	// ----- Historical Variables -----
	protected $files;			// array('filename1.ext', 'filename2.ext')
	protected $source;			// string = 'path/to/directory'
	protected $destination;		// string = 'path/to/directory'
	protected $overwrite;		// bool (true/false)

	// ----- Process Variables -----
	protected $processID;		// ID of the specified process (in case of multiples, for logging)
	protected $processedFiles;	// array(0 => array(filename => 'filename1.ext', action => 'copy', success => true, 'processID' = 1), 1 => array(filename => 'filename2.ext', action => 'copy', success => false, 'processID' = 1))
	protected $opStatus;		// Operation Status
	protected $message;			// Status Messages regarding success/failure and notes

	/***** Default Constructor *****/
	public function __construct()
	{
		// Do we need to initialize anything?
		$this->processID = 0;
		$this->opStatus  = true;
		$this->message[$this->processID][] = 'fileManipulation Class Instantiated.';
	}

	/***** getStatus ***********************************************************
	 * Description: Returns the last operation status, message, and
	 *              file processing history (if exists).
	 * 
	 * @return array
	 **************************************************************************/
	public function getStatus()
	{
		return array(
			 'status'  => $this->opStatus
			,'message' => $this->message
			,'files'   => (!empty($this->processedFiles)) ? $this->processedFiles : $this->files
		);
	}

	/***** createFiles *********************************************************
	 * Description: Create a list of files at the specified source location.
	 **************************************************************************/
	public function createFiles()
	{
		// TODO: Create function, if necessary.
	}

	/***** copyFiles ***********************************************************
	 * Description: Copy files from one location to another
	 *              (with overwrite protection)
	 * 
	 * @param array $files
	 * @param string $source
	 * @param string $destination
	 * @param boolean $overwrite
	 * @return boolean
	 **************************************************************************/
	public function copyFiles($files = array(), $source = '', $destination = '', $overwrite = false)
	{
		// ----- Initialize process variables -----
		$this->opStatus = false;
		$this->message[$this->processID][] = 'Copy method starting...';
		// ----- Verify all values necessary for function are present -----
		if (!empty($files) && !empty($source) && !empty($destination)) {
			$this->message[$this->processID][] = 'Copy procedure: required parameters verified.';
			// ----- Store parameters for historical -----
			$this->files = $files;
			$this->source = $source;
			$this->destination = $destination;
			$this->overwrite = $overwrite;
			// ----- Overwrite Protection -----
			if ($overwrite === false && $this->verifyFiles($files, $destination) === true) {
				$this->message[$this->processID][] = 'ERROR: Copy procedure failed. Unable to overwrite existing files.';
				return $this->opStatus;
			}
			// ----- Copy Procedure -----
			if ($this->verifyFiles($files, $source, true) === true) {
				$fileCount = count($files);
				$this->processID++;
				foreach ($files as $filename) {
					$copy = copy($source.'\\'.$filename, $destination.'\\'.$filename);
					$this->processedFiles[$this->processID][] = array(
						'filename' => $filename
						,'action' => 'copy'
						,'success' => $copy
					);
					if ($copy === true) {
						$fileCount--;
					}
				}
				// ----- Status for Copy Procedure -----
				if ($fileCount === 0) {
					// If file count is 0, all files copied successfully! #winning
					$this->opStatus = true;
					$this->message[$this->processID][] = 'SUCCESS: Copy procedure succeeded (see log).';
				} else {
					// If file count is greater than 0, some files failed to copy.
					$this->message[$this->processID][] = "ERROR: Copy procedure failed. {$fileCount} file(s) failed to copy (see log).";
				}
			} else {
				// If files are missing in the source directory.
				$this->message[$this->processID][] = "ERROR: Copy procedure failed. Files missing in source directory (see log).";
			}
		} else {
			// ----- Error for missing required parameters -----
			$this->message[$this->processID][] = 'ERROR: Copy procedure failed. Required parameters missing.';
		}
		return $this->opStatus;
	}

	/***** deleteFiles *********************************************************
	 * Description: Delete any of the files in the source folder that exist.
	 * 
	 * @param array $files
	 * @param string $source
	 * @return boolean
	 **************************************************************************/
	public function deleteFiles($files = array(), $source = '')
	{
		// ----- Initialize process variables -----
		$this->opStatus = false;
		// ----- Verify all values necessary for function are present -----
		if (!empty($files) && !empty($source)) {
			$this->message[$this->processID][] = 'Delete procedure: required parameters verified.';
			// ----- Store parameters for historical -----
			$this->files = $files;
			$this->source = $source;
			// ----- Delete Procedure -----
			$this->message[$this->processID][] = 'Delete method starting...';
			$fileCount = count($files);
			$this->processID++;
			foreach ($files as $filename) {
				// @ inhibits PHP error log reporting per command.
				$delete = @unlink($source.'\\'.$filename);
				$this->processedFiles[$this->processID][] = array(
					'filename' => $filename
					,'action' => 'delete'
					,'success' => $delete
				);
				if ($delete === true) {
					$fileCount--;
				}
			}
			// ----- Status for Verify Procedure -----
			if ($fileCount === 0) {
				// If file count is 0, all files deleted successfully! #winning
				$this->opStatus = true;
				$this->message[$this->processID][] = 'SUCCESS: Delete procedure succeeded (see log).';
			} else {
				// If file count is greater than 0, some files failed to delete.
				$this->message[$this->processID][] = "ERROR: Delete procedure failed. {$fileCount} file(s) failed to delete (see log).";
			}
		} else {
			// ----- Error for missing required parameters -----
			$this->message[$this->processID][] = 'ERROR: Delete procedure failed. Required parameters missing.';
		}
		return $this->opStatus;
	}

	/***** verifyFiles *********************************************************
	 * Description: Verify if files are present at the source location.
	 *              Defaults to verify if ANY of the files are present.
	 * 
	 * @param array $files
	 * @param string $source
	 * @param boolean $allFiles Set to true to verify if ALL files are present
	 * @return boolean 
	 **************************************************************************/
	public function verifyFiles($files = array(), $source = '', $allFiles = false)
	{
		// ----- Initialize process variables -----
		$fileCount = count($files);
		$totFileCount = count($files);
		// ----- Verify all values necessary for function are present -----
		if (!empty($files) && !empty($source)) {
			$this->message[$this->processID][] = 'Verify procedure: required parameters verified.';
			// ----- Verify Procedure -----
			$this->message[$this->processID][] = 'Verify method starting...';
			$this->processID++;
			foreach ($files as $filename) {
				$verify = file_exists($source.'\\'.$filename);
				$this->processedFiles[$this->processID][] = array(
					'filename' => $filename
					,'action' => 'verify'
					,'success' => $verify
				);
				if ($verify === true) {
					$fileCount--;
				}
			}
			// ----- Status for Verify Procedure -----
			if ($fileCount === $totFileCount) {
				$this->message[$this->processID][] = 'Verify procedure: No files were found (see log).';
				return false;
			} elseif ($fileCount === 0) {
				$this->message[$this->processID][] = 'Verify procedure: All files were found (see log).';
				return true;
			} else {
				$this->message[$this->processID][] = "Verify procedure: ".($totFileCount - $fileCount)." file(s) were found (see log).";
				return ($allFiles === true) ? false : true;
			}
		} else {
			// ----- Error for missing required parameters -----
			$this->message[$this->processID][] = 'ERROR: Verify procedure failed. Required parameters missing.';
			return null;
		}
	}

	public function moveFiles($files = array(), $source = '', $destination = '', $overwrite = false)
	{
		// Uses Copy, Verify, and Delete.
		$copySuccess = $this->copyFiles($files, $source, $destination, $overwrite);
		// If copy successful, verify files.
		if ($copySuccess === true && $this->verifyFiles($files, $destination, true) === true) {
			// If verify successful, delete files.
			return $this->deleteFiles($files, $source);
		}
		// Move failed.
		$this->message[$this->processID][] = 'ERROR: Move procedure failed.';
		return false;
	}

	/***** Default Destructor *****/
	public function __destruct()
	{
		
	}
}